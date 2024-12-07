<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Crm\Integration\StorageType;
use \Bitrix\Crm\Conversion\LeadConversionScheme;
use \Bitrix\Crm\Category\DealCategory;
use \Bitrix\Crm\Conversion\EntityConverter;
use Bitrix\Crm\Restriction\RestrictionManager;

global $APPLICATION;
$APPLICATION->AddHeadScript('/bitrix/js/crm/instant_editor.js');
$APPLICATION->AddHeadScript('/bitrix/js/crm/dialog.js');

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

//Preliminary registration of disk api.
if(CCrmActivity::GetDefaultStorageTypeID() === StorageType::Disk)
{
	CJSCore::Init(array('uploader', 'file_dialog'));
}

$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(
	'CRM_LEAD_SHOW_TITLE',
	array(
		'#ID#' => $arResult['ELEMENT']['ID'],
		'#TITLE#' => $arResult['ELEMENT']['TITLE']
	)
);

$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'];
$instantEditorID = mb_strtolower($arResult['FORM_ID']).'_editor';
$bizprocDispatcherID = mb_strtolower($arResult['FORM_ID']).'_bp_disp';
$treeDispatcherID = mb_strtolower($arResult['FORM_ID']).'_tree_disp';

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1'],
	'display' => false
);
if(!empty($arResult['FIELDS']['tab_details']))
{
	$arTabs[] = array(
		'id' => 'tab_details',
		'name' => GetMessage('CRM_TAB_DETAILS'),
		'title' => GetMessage('CRM_TAB_DETAILS_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_details'],
		'display' => false
	);
}

$liveFeedTab = null;
if (!empty($arResult['FIELDS']['tab_live_feed']))
{
	$liveFeedTab = array(
		'id' => 'tab_live_feed',
		'name' => GetMessage('CRM_TAB_LIVE_FEED'),
		'title' => GetMessage('CRM_TAB_LIVE_FEED_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_live_feed']
	);
	$arTabs[] = $liveFeedTab;
}
if (!empty($arResult['FIELDS']['tab_activity']))
{
	$arTabs[] = array(
		'id' => 'tab_activity',
		'name' => GetMessage('CRM_TAB_6'),
		'title' => GetMessage('CRM_TAB_6_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_activity']
	);
}
$arTabs[] = array(
	'id' => $arResult['PRODUCT_ROW_TAB_ID'],
	'name' => GetMessage('CRM_TAB_PRODUCT_ROWS'),
	'title' => GetMessage('CRM_TAB_PRODUCT_ROWS_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS'][$arResult['PRODUCT_ROW_TAB_ID']]
);
if ($arResult['ELEMENT']['STATUS_ID'] == 'CONVERTED'):
	if (!empty($arResult['FIELDS']['tab_contact']))
		$arTabs[] = array(
			'id' => 'tab_contact',
			//'name' => GetMessage('CRM_TAB_2')." ($arResult[CONTACT_COUNT])",
			'name' => GetMessage('CRM_TAB_2'),
			'title' => GetMessage('CRM_TAB_2_TITLE'),
			'icon' => '',
			'fields'=> $arResult['FIELDS']['tab_contact']
		);
	if (!empty($arResult['FIELDS']['tab_company']))
		$arTabs[] = array(
			'id' => 'tab_company',
			//'name' => GetMessage('CRM_TAB_3')." ($arResult[COMPANY_COUNT])",
			'name' => GetMessage('CRM_TAB_3'),
			'title' => GetMessage('CRM_TAB_3_TITLE'),
			'icon' => '',
			'fields'=> $arResult['FIELDS']['tab_company']
		);
	if (!empty($arResult['FIELDS']['tab_deal']))
		$arTabs[] = array(
			'id' => 'tab_deal',
			//'name' => GetMessage('CRM_TAB_4')." ($arResult[DEAL_COUNT])",
			'name' => GetMessage('CRM_TAB_4'),
			'title' => GetMessage('CRM_TAB_4_TITLE'),
			'icon' => '',
			'fields'=> $arResult['FIELDS']['tab_deal']
		);
endif;

if (!empty($arResult['FIELDS']['tab_quote']))
{
	$arTabs[] = array(
		'id' => 'tab_quote',
		'name' => GetMessage('CRM_TAB_8'),
		'title' => GetMessage('CRM_TAB_8_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_quote']
	);
}
if (!empty($arResult['FIELDS']['tab_automation']))
{
	$arTabs[] = array(
		'id' => 'tab_automation',
		'name' => GetMessage('CRM_TAB_AUTOMATION'),
		'title' => GetMessage('CRM_TAB_AUTOMATION_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_automation']
	);
}
if (isset($arResult['BIZPROC']) && $arResult['BIZPROC'] === 'Y' && !empty($arResult['FIELDS']['tab_bizproc']))
{
	$arTabs[] = array(
		'id' => 'tab_bizproc',
		'name' => GetMessage('CRM_TAB_7'),
		'title' => GetMessage('CRM_TAB_7_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_bizproc']
	);
}
$arTabs[] = array(
	'id' => 'tab_tree',
	'name' => GetMessage('CRM_TAB_TREE'),
	'title' => GetMessage('CRM_TAB_TREE_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_tree']
);
$tabEventParams = [
	'id' => 'tab_event',
	//'name' => GetMessage('CRM_TAB_HISTORY')." ($arResult[EVENT_COUNT])",
	'name' => GetMessage('CRM_TAB_HISTORY'),
	'title' => GetMessage('CRM_TAB_HISTORY_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_event']
];
if (isset($arResult['TAB_EVENT_TARIFF_LOCK']) && $arResult['TAB_EVENT_TARIFF_LOCK'] === 'Y')
{
	$tabEventParams['tariffLock']  = RestrictionManager::getHistoryViewRestriction()->prepareInfoHelperScript();
}
$arTabs[] = $tabEventParams;
unset($tabEventParams);
if(!empty($arResult['LISTS']))
{
	foreach($arResult['LIST_IBLOCK'] as $iblockId => $iblockName)
	{
		$arTabs[] = array(
			'id' => 'tab_lists_'.$iblockId,
			'name' => $iblockName,
			'title' => $iblockName,
			'icon' => '',
			'fields' => $arResult['FIELDS']['tab_lists_'.$iblockId]
		);
	}
}

$element = isset($arResult['ELEMENT']) ? $arResult['ELEMENT'] : null;

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.quickpanelview',
	'',
	array(
		'GUID' => mb_strtolower($arResult['FORM_ID']).'_qpv',
		'FORM_ID' => $arResult['TACTILE_FORM_ID'],
		'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
		'ENTITY_ID' => $arResult['ELEMENT_ID'],
		'ENTITY_FIELDS' => $element,
		'ENABLE_INSTANT_EDIT' => $arResult['ENABLE_INSTANT_EDIT'],
		'INSTANT_EDITOR_ID' => $instantEditorID,
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.show/ajax.php?'.bitrix_sessid_get(),
		'CONVERSION_SCHEME' => isset($arResult['CONVERSION_SCHEME']) ? $arResult['CONVERSION_SCHEME'] : null,
		'CONVERSION_TYPE_ID' => $arResult['CONVERSION_TYPE_ID'],
		'CAN_CONVERT' => $arResult['CAN_CONVERT'],
		'SHOW_SETTINGS' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'show',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TACTILE_FORM_ID' => $arResult['TACTILE_FORM_ID'],
		'TABS' => $arTabs,
		'DATA' => $element,
		'SHOW_SETTINGS' => 'Y'
	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>
<?if($arResult['CAN_CONVERT'] && isset($arResult['CONVERSION_CONFIGS'])):?>
<script>
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

			BX.CrmLeadConversionType.configs = <?=CUtil::PhpToJSObject($arResult['CONVERSION_CONFIGS'])?>;
			BX.CrmLeadConversionScheme.messages =
				<?=CUtil::PhpToJSObject(LeadConversionScheme::getJavaScriptDescriptions(false))?>;
			BX.CrmLeadConverter.messages =
			{
				accessDenied: "<?=GetMessageJS("CRM_LEAD_CONV_ACCESS_DENIED")?>",
				generalError: "<?=GetMessageJS("CRM_LEAD_CONV_GENERAL_ERROR")?>",
				dialogTitle: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_TITLE")?>",
				syncEditorLegend: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_SYNC_LEGEND")?>",
				syncEditorFieldListTitle: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
				syncEditorEntityListTitle: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
				continueButton: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_CONTINUE_BTN")?>",
				cancelButton: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_CANCEL_BTN")?>",
				selectButton: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_BTN")?>",
				openEntitySelector: "<?=GetMessageJS("CRM_LEAD_CONV_OPEN_ENTITY_SEL")?>",
				entitySelectorTitle: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_TITLE")?>",
				contact: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_CONTACT")?>",
				company: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_COMPANY")?>",
				noresult: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_SEARCH_NO_RESULT")?>",
				search : "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_SEARCH")?>",
				last : "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_LAST")?>"
			};
			BX.CrmLeadConverter.permissions =
			{
				contact: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_CONTACT'])?>,
				company: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_COMPANY'])?>,
				deal: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_DEAL'])?>
			};
			BX.CrmLeadConverter.settings =
			{
				serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.show/ajax.php?action=convert&'.bitrix_sessid_get()?>"
			};
			BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject(
				DealCategory::getJavaScriptInfos(EntityConverter::getPermittedDealCategoryIDs())
			)?>;
			BX.CrmDealCategorySelectDialog.messages =
			{
				title: "<?=GetMessageJS('CRM_LEAD_SHOW_CONV_DEAL_CATEGORY_DLG_TITLE')?>",
				field: "<?=GetMessageJS('CRM_LEAD_SHOW_CONV_DEAL_CATEGORY_DLG_FIELD')?>",
				saveButton: "<?=GetMessageJS('CRM_LEAD_SHOW_BUTTON_SAVE')?>",
				cancelButton: "<?=GetMessageJS('CRM_LEAD_SHOW_BUTTON_CANCEL')?>"
			};
		}
	);
</script>
<?endif;?>

<?if($arResult['ENABLE_INSTANT_EDIT']):?>
<script>
	BX.ready(
		function()
		{
			BX.CrmInstantEditorMessages =
			{
				editButtonTitle: '<?= CUtil::JSEscape(GetMessage('CRM_EDIT_BTN_TTL'))?>',
				lockButtonTitle: '<?= CUtil::JSEscape(GetMessage('CRM_LOCK_BTN_TTL'))?>'
			};

			var instantEditor = BX.CrmInstantEditor.create(
				'<?=CUtil::JSEscape($instantEditorID)?>',
				{
					containerID: [],
					ownerType: 'L',
					ownerID: <?=$arResult['ELEMENT_ID']?>,
					url: '/bitrix/components/bitrix/crm.lead.show/ajax.php?<?= bitrix_sessid_get()?>',
					callToFormat: <?=CCrmCallToUrl::GetFormat(CCrmCallToUrl::Bitrix)?>
				}
			);

			var prodEditor = BX.CrmProductEditor.getDefault();

			function handleProductRowChange()
			{
				if(prodEditor)
				{
					var haveProducts = prodEditor.getProductCount() > 0;
					instantEditor.setFieldReadOnly('OPPORTUNITY', haveProducts);
					instantEditor.setFieldReadOnly('CURRENCY_ID', haveProducts);
				}
			}

			function handleSelectProductEditorTab(objForm, objFormName, tabID, tabElement)
			{
				var productRowsTabId = "<?=$arResult['PRODUCT_ROW_TAB_ID']?>";
				if (typeof(productRowsTabId) === "string" && productRowsTabId.length > 0 && tabID === productRowsTabId)
					BX.onCustomEvent("CrmHandleShowProductEditor", [prodEditor]);
			}

			if(prodEditor)
			{
				BX.addCustomEvent(
					prodEditor,
					'sumTotalChange',
					function(ttl)
					{
						instantEditor.setFieldValue('OPPORTUNITY', ttl);
						if(prodEditor.isViewMode())
						{
							//emulate save field event to refresh controls
							instantEditor.riseSaveFieldValueEvent('OPPORTUNITY', ttl);
						}
					}
				);

				handleProductRowChange();

				BX.addCustomEvent(
					prodEditor,
					'productAdd',
					handleProductRowChange
				);

				BX.addCustomEvent(
					prodEditor,
					'productRemove',
					handleProductRowChange
				);

				BX.addCustomEvent(
					'BX_CRM_INTERFACE_FORM_TAB_SELECTED',
					handleSelectProductEditorTab
				);
			}
		}
	);
</script>
<?endif;?>

<script>
	BX.ready(function(){
		BX.Crm.PartialEditorDialog.registerEntityEditorUrl(
			"<?=CCrmOwnerType::LeadName?>",
			"<?='/bitrix/components/bitrix/crm.lead.details/ajax.php?'.bitrix_sessid_get()?>"
		);

		var treeContainerId = '<?=$arResult['TREE_CONTAINER_ID']?>';
		if (!BX(treeContainerId))
		{
			return;
		}

		BX.CrmEntityTreeDispatcher.create(
			'dispatcher<?= CUtil::JSEscape($treeDispatcherID)?>',
			{
				containerID: treeContainerId,
				entityTypeName: '<?= CCrmOwnerType::LeadName?>',
				entityID: <?=$arResult['ELEMENT_ID']?>,
				serviceUrl: '/bitrix/components/bitrix/crm.entity.tree/ajax.php?<?=bitrix_sessid_get()?>',
				formID: '<?= CUtil::JSEscape($arResult['FORM_ID'])?>',
				selected: <?= $arResult['TAB_TREE_OPEN'] ? 'true' : 'false'?>,
				pathToLeadShow: '<?= CUtil::JSEscape($arParams['PATH_TO_LEAD_SHOW'])?>',
				pathToContactShow: '<?= CUtil::JSEscape($arParams['PATH_TO_CONTACT_SHOW'])?>',
				pathToCompanyShow: '<?= CUtil::JSEscape($arParams['PATH_TO_COMPANY_SHOW'])?>',
				pathToDealShow: '<?= CUtil::JSEscape($arParams['PATH_TO_DEAL_SHOW'])?>',
				pathToQuoteShow: '<?= CUtil::JSEscape($arParams['PATH_TO_QUOTE_SHOW'])?>',
				pathToInvoiceShow: '<?= CUtil::JSEscape($arParams['PATH_TO_INVOICE_SHOW'])?>',
				pathToUserProfile: '<?=CUtil::JSEscape($arParams['PATH_TO_USER_PROFILE'])?>'
			}
		);
	});
</script>

<?if(isset($arResult['ENABLE_BIZPROC_LAZY_LOADING']) && $arResult['ENABLE_BIZPROC_LAZY_LOADING'] === true):?>
<script>
	BX.ready(
		function()
		{
			var bpContainerId = "<?=$arResult['BIZPROC_CONTAINER_ID']?>";
			if(!BX(bpContainerId))
			{
				return;
			}

			BX.CrmBizprocDispatcher.create(
				"<?=CUtil::JSEscape($bizprocDispatcherID)?>",
				{
					containerID: bpContainerId,
					entityTypeName: "<?=CCrmOwnerType::LeadName?>",
					entityID: <?=$arResult['ELEMENT_ID']?>,
					serviceUrl: "/bitrix/components/bitrix/crm.lead.show/bizproc.php?lead_id=<?=$arResult['ELEMENT_ID']?>&post_form_uri=<?=urlencode($arResult['POST_FORM_URI'])?>&<?=bitrix_sessid_get()?>",
					formID: "<?=CUtil::JSEscape($arResult['FORM_ID'])?>",
					pathToEntityShow: "<?=CUtil::JSEscape($arResult['PATH_TO_LEAD_SHOW'])?>"
				}
			);
		}
	);
</script>
<?endif;?>

<?if(isset($arResult['ENABLE_LIVE_FEED_LAZY_LOAD']) && $arResult['ENABLE_LIVE_FEED_LAZY_LOAD'] === true):?>
<script>
	BX.ready(
		function()
		{
			var liveFeedContainerId = "<?=CUtil::JSEscape($arResult['LIVE_FEED_CONTAINER_ID'])?>";
			if(!BX(liveFeedContainerId))
			{
				return;
			}

			var params =
			{
				"ENTITY_TYPE_NAME" : "<?=CCrmOwnerType::LeadName?>",
				"ENTITY_ID": <?=$arResult['ELEMENT_ID']?>,
				"POST_FORM_URI": "<?=CUtil::JSEscape($arResult['POST_FORM_URI'])?>",
				"ACTION_URI": "<?=CUtil::JSEscape($arResult['ACTION_URI'])?>",
				"PATH_TO_USER_PROFILE": "<?=CUtil::JSEscape($arParams['PATH_TO_USER_PROFILE'])?>"
			};

			BX.addCustomEvent(
				window,
				"SonetLogBeforeGetNextPage",
				function(data)
					{
						if(!BX.type.isNotEmptyString(data["url"]))
						{
							return;
						}

						var request = {};
						for(var key in params)
						{
							if(params.hasOwnProperty(key))
							{
								request["PARAMS[" + key + "]"] = params[key];
							}
						}
						data["url"] = BX.util.add_url_param(data["url"], request);
					}
			);

			BX.CrmFormTabLazyLoader.create(
				"<?=CUtil::JSEscape(mb_strtolower($arResult['FORM_ID'])).'_livefeed'?>",
				{
					containerID: liveFeedContainerId,
					serviceUrl: "/bitrix/components/bitrix/crm.entity.livefeed/lazyload.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
					formID: "<?=CUtil::JSEscape($arResult['FORM_ID'])?>",
					tabID: "tab_live_feed",
					params: params
				}
			);
		}
	);
</script>
<?endif;?>
