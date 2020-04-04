<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

if(isset($arResult['CONVERSION_LEGEND'])):
	?><div class="crm-view-message"><?=$arResult['CONVERSION_LEGEND']?></div><?
endif;

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1']
);

$productFieldset = array();
foreach($arTabs[0]['fields'] as $k => &$field):
	if($field['id'] === 'section_product_rows'):
		$productFieldset['NAME'] = $field['name'];
		unset($arTabs[0]['fields'][$k]);
	endif;

	if($field['id'] === 'PRODUCT_ROWS'):
		$productFieldset['HTML'] = $field['value'];
		unset($arTabs[0]['fields'][$k]);
		break;
	endif;
endforeach;
unset($field);

$elementID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;

if ($elementID > 0)
{
	$titleCode = $arParams['IS_RECURRING'] === 'Y' ? 'CRM_DEAL_RECUR_SHOW_TITLE' : 'CRM_DEAL_EDIT_TITLE';
	$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(
		$titleCode,
		array(
			'#ID#' => $arResult['ELEMENT']['ID'],
			'#TITLE#' => $arResult['ELEMENT']['TITLE']
		)
	);
}
else
{
	$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage('CRM_DEAL_CREATE_TITLE');
}

$arFormButtons = array(
	'back_url' => $arResult['BACK_URL'],
	'custom_html' => '<input type="hidden" name="deal_id" value="'.$elementID.'"/>'
);

if($arResult['CATEGORY_ID'] > 0)
{
	$arFormButtons['custom_html'] .= '<input type="hidden" name="category_id" value="'.$arResult['CATEGORY_ID'].'"/>';
}

if($arResult['CALL_LIST_ID'] > 0)
{
	$arFormButtons['custom_html'] .= '<input type="hidden" name="call_list_id" value="'.(int)$arResult['CALL_LIST_ID'].'"/>';
	$arFormButtons['custom_html'] .= '<input type="hidden" name="call_list_element" value="'.(int)$arResult['CALL_LIST_ELEMENT'].'"/>';
}

if(isset($arResult['LEAD_ID']) && $arResult['LEAD_ID'] > 0)
{
	$arFormButtons['standard_buttons'] = false;
	$arFormButtons['wizard_buttons'] = true;
	$arFormButtons['custom_html'] = '<input type="hidden" name="lead_id" value="'.$arResult['LEAD_ID'].'"/>';
}
elseif(isset($arResult['QUOTE_ID']) && $arResult['QUOTE_ID'] > 0)
{
	$arFormButtons['standard_buttons'] = false;
	$arFormButtons['wizard_buttons'] = true;
	$arFormButtons['custom_html'] .= '<input type="hidden" name="quote_id" value="'.$arResult['QUOTE_ID'].'"/>';
}
elseif(isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] !== '')
{
	$arFormButtons['standard_buttons'] = false;
	$arFormButtons['dialog_buttons'] = true;
	$arFormButtons['wizard_buttons'] = false;
	$arFormButtons['custom_html'] .= '<input type="hidden" name="external_context" value="'.htmlspecialcharsbx($arResult['EXTERNAL_CONTEXT']).'"/>';
}
else
{
	$arFormButtons['standard_buttons'] = true;
	$arFormButtons['wizard_buttons'] = false;
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'edit',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'FIELD_SETS' => array($productFieldset),
		'BUTTONS' => $arFormButtons,
		'IS_NEW' => $elementID <= 0,
		'USER_FIELD_ENTITY_ID' => CCrmDeal::$sUFEntityID,
		'USER_FIELD_SERVICE_URL' => '/bitrix/components/bitrix/crm.config.fields.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
		'TITLE' => $arResult['CRM_CUSTOM_PAGE_TITLE'],
		'ENABLE_TACTILE_INTERFACE' => 'Y',
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	)
);
?>
<script type="text/javascript">

	window.CrmProductRowSetLocation = function(){ BX.onCustomEvent('CrmProductRowSetLocation', ['LOC_CITY']); };

	BX.ready(
		function()
		{
			var formID = 'form_' + '<?= $arResult['FORM_ID'] ?>';
			var form = BX(formID);

			var currencyEl = BX.findChild(form, { 'tag':'select', 'attr':{ 'name': 'CURRENCY_ID' } }, true, false);
			var opportunityEl = BX.findChild(form, { 'tag':'input', 'attr':{ 'name': 'OPPORTUNITY' } }, true, false);

			var prodEditor = BX.CrmProductEditor.getDefault();
			if(opportunityEl)
			{
				opportunityEl.disabled = prodEditor.getProductCount() > 0;

				BX.addCustomEvent(
					prodEditor,
					'productAdd',
					function(params)
					{
						opportunityEl.disabled = prodEditor.getProductCount() > 0;
					}
				);

				BX.addCustomEvent(
					prodEditor,
					'productRemove',
					function(params)
					{
						opportunityEl.disabled = prodEditor.getProductCount() > 0;
					}
				);

				BX.addCustomEvent(
					prodEditor,
					'sumTotalChange',
					function(ttl)
					{
						opportunityEl.value = ttl;
					}
				);

				if(currencyEl)
				{
					BX.bind(
						currencyEl,
						'change',
						function()
						{
							var currencyId = currencyEl.value;
							var prevCurrencyId = prodEditor.getCurrencyId();

							prodEditor.setCurrencyId(currencyId);

							var oportunity = opportunityEl.value.length > 0 ? parseFloat(opportunityEl.value) : 0;
							if(isNaN(oportunity))
							{
								oportunity = 0;
							}

							if(prodEditor.getProductCount() == 0 && oportunity !== 0)
							{
								prodEditor.convertMoney(
									parseFloat(opportunityEl.value),
									prevCurrencyId,
									currencyId,
									function(sum)
									{
										opportunityEl.value = sum;
									}
								);
							}
						}
					);
				}
			}

			var el = BX("LOC_CITY_val");
			if (el)
				BX.addClass(el, "bx-crm-edit-input");
		}
	);
</script>
<?
if($arResult['CONVERSION_PERMITTED'] && $arResult['CAN_CONVERT'] && isset($arResult['CONVERSION_CONFIG'])):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.CrmEntityType.captions =
				{
					"<?=CCrmOwnerType::LeadName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Lead)?>",
					"<?=CCrmOwnerType::ContactName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Contact)?>",
					"<?=CCrmOwnerType::CompanyName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Company)?>",
					"<?=CCrmOwnerType::DealName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Deal)?>",
					"<?=CCrmOwnerType::InvoiceName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Invoice)?>",
					"<?=CCrmOwnerType::QuoteName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Quote)?>"
				};

				BX.CrmDealConversionScheme.messages =
					<?=CUtil::PhpToJSObject(\Bitrix\Crm\Conversion\DealConversionScheme::getJavaScriptDescriptions(false))?>;

				BX.CrmDealConverter.messages =
				{
					accessDenied: "<?=GetMessageJS("CRM_DEAL_CONV_ACCESS_DENIED")?>",
					generalError: "<?=GetMessageJS("CRM_DEAL_CONV_GENERAL_ERROR")?>",
					dialogTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_TITLE")?>",
					syncEditorLegend: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_LEGEND")?>",
					syncEditorFieldListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
					syncEditorEntityListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
					continueButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CONTINUE_BTN")?>",
					cancelButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CANCEL_BTN")?>"
				};
				BX.CrmDealConverter.permissions =
				{
					invoice: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_INVOICE'])?>,
					quote: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_QUOTE'])?>
				};
				BX.CrmDealConverter.settings =
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.show/ajax.php?action=convert&'.bitrix_sessid_get()?>",
					config: <?=CUtil::PhpToJSObject($arResult['CONVERSION_CONFIG']->toJavaScript())?>
				};
			}
		);
	</script>
<?endif;?>
