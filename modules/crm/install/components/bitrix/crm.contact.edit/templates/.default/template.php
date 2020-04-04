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

$entityTypeCategories = CCrmOwnerType::GetAllCategoryCaptions();
$elementID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;

$arResult['CRM_CUSTOM_PAGE_TITLE'] =
	$elementID > 0
	? GetMessage('CRM_CONTACT_EDIT_TITLE',
		array(
			'#ID#' => $elementID,
			'#NAME#' => CCrmContact::PrepareFormattedName(
				array(
					'HONORIFIC' => isset($arResult['ELEMENT']['~HONORIFIC']) ? $arResult['ELEMENT']['~HONORIFIC'] : '',
					'NAME' => isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : '',
					'LAST_NAME' => isset($arResult['ELEMENT']['~LAST_NAME']) ? $arResult['ELEMENT']['~LAST_NAME'] : '',
					'SECOND_NAME' => isset($arResult['ELEMENT']['~SECOND_NAME']) ? $arResult['ELEMENT']['~SECOND_NAME'] : ''
				)
			)
		)
	)
	: GetMessage('CRM_CONTACT_CREATE_TITLE');

$arFormButtons = array(
	'back_url' => $arResult['BACK_URL'],
	'standard_buttons' => true,
	'wizard_buttons' => false,
	'custom_html' => '<input type="hidden" name="contact_id" value="'.$elementID.'"/>'
);

if(isset($arResult['LEAD_ID']) && $arResult['LEAD_ID'] > 0)
{
	$arFormButtons['standard_buttons'] = false;
	$arFormButtons['wizard_buttons'] = true;
	$arFormButtons['custom_html'] .= '<input type="hidden" name="lead_id" value="'.$arResult['LEAD_ID'].'"/>';
}
elseif(isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] !== '')
{
	$arFormButtons['standard_buttons'] = false;
	$arFormButtons['dialog_buttons'] = true;
	$arFormButtons['custom_html'] .= '<input type="hidden" name="external_context" value="'.htmlspecialcharsbx($arResult['EXTERNAL_CONTEXT']).'"/>';
}

if(
	isset($arResult['REST_APPLICATION_LIST'])
	&& count($arResult['REST_APPLICATION_LIST']) > 0
)
{
?>
	<div style="margin-bottom: 9px">
<?
	$APPLICATION->IncludeComponent(
		'bitrix:app.placement',
		'',
		array(
			'PLACEMENT' => "CRM_CONTACT_EDIT",
			'PLACEMENT_APP' => 11,
			"PLACEMENT_OPTIONS" => array(
				'ID' => $elementID,
			),
			'PARAM' => array(
				'FRAME_HEIGHT' => '200px'
			),
			'SAVE_LAST_APP' => 'N',
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
?>
	</div>
	<script>
		BX.onCustomEvent('onCrmContactEditInterfaceInit', [{
			events: ['onContactFormChange'],
			setCrmFormFields: function(params, cb)
			{
				for(var i in params)
				{
					if(params.hasOwnProperty(i))
					{
						if(!!document.forms['form_<?=$arResult['FORM_ID']?>'].elements[i])
						{
							document.forms['form_<?=$arResult['FORM_ID']?>'].elements[i].value = params[i];
						}
					}
				}

				cb();
			}
		}]);

		BX.ready(function(){
var form = document.forms['form_<?=$arResult['FORM_ID']?>'];

var f = function()
{
	var data = BX.ajax.prepareForm(form).data;
	delete data.sessid;
	BX.onCustomEvent('OnCrmFormChange', [data]);
};

for(var i = 0; i < form.elements.length; i++)
{
	BX.bind(form.elements[i], 'change', f);
	BX.bind(form.elements[i], 'keyup', f);
}
		});
	</script>
<?
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'edit',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => $arFormButtons,
		'IS_NEW' => $elementID <= 0,
		'USER_FIELD_ENTITY_ID' => CCrmContact::$sUFEntityID,
		'USER_FIELD_SERVICE_URL' => '/bitrix/components/bitrix/crm.config.fields.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
		'TITLE' => $arResult['CRM_CUSTOM_PAGE_TITLE'],
		'ENABLE_TACTILE_INTERFACE' => 'Y',
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	)
);

$crmEmail = strtolower(COption::GetOptionString('crm', 'mail', ''));
if ($arResult['ELEMENT']['ID'] == 0 && $crmEmail != ''):
?><div class="crm_notice_message"><?=GetMessage('CRM_IMPORT_SNS', Array('%EMAIL%' => $crmEmail, '%ARROW%' => '<span class="crm_notice_arrow"></span>'));?></div><?
endif;
if($arResult['DUPLICATE_CONTROL']['ENABLED']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var formID = "form_" + "<?=CUtil::JSEscape($arResult['FORM_ID'])?>";
			var form = BX(formID);

			BX.CrmDuplicateSummaryPopup.messages =
			{
				title: "<?=GetMessageJS("CRM_CONTACT_EDIT_DUP_CTRL_SHORT_SUMMARY_TITLE")?>"
			};

			BX.CrmDuplicateWarningDialog.messages =
			{
				title: "<?=GetMessageJS("CRM_CONTACT_EDIT_DUP_CTRL_WARNING_DLG_TITLE")?>",
				acceptButtonTitle: "<?=GetMessageJS("CRM_CONTACT_EDIT_DUP_CTRL_WARNING_ACCEPT_BTN_TITLE")?>",
				cancelButtonTitle: "<?=GetMessageJS("CRM_CONTACT_EDIT_DUP_CTRL_WARNING_CANCEL_BTN_TITLE")?>"
			};

			BX.CrmEntityType.categoryCaptions =
			{
				"<?=CCrmOwnerType::LeadName?>": "<?=$entityTypeCategories[CCrmOwnerType::Lead]?>",
				"<?=CCrmOwnerType::ContactName?>": "<?=$entityTypeCategories[CCrmOwnerType::Contact]?>",
				"<?=CCrmOwnerType::CompanyName?>": "<?=$entityTypeCategories[CCrmOwnerType::Company]?>",
				"<?=CCrmOwnerType::DealName?>": "<?=$entityTypeCategories[CCrmOwnerType::Deal]?>",
				"<?=CCrmOwnerType::InvoiceName?>": "<?=$entityTypeCategories[CCrmOwnerType::Invoice]?>"
			};

			//DUPLICATE CONTROL
			var dupControllerId = (formID.toLowerCase() + "_dup");
			var dupControllerRequisite = BX.CrmDupControllerRequisite.create(
				(formID.toLowerCase() + "_dup_rq"),
				{
					"dupControllerId": dupControllerId,
					"dupFieldsMap": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_FIELDS_MAP'])?>,
					"dupFieldsDescriptions": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_FIELDS_DESCR'])?>,
					"dupCountriesInfo": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_COUNTRIES_INFO'])?>,
					"groupSummaryTitle": "<?=GetMessageJS("CRM_CONTACT_EDIT_DUP_CTRL_REQUISITE_SUMMARY_TITLE")?>"
				}
			);
			var dupControllerBankDetail = BX.CrmDupControllerBankDetail.create(
				(formID.toLowerCase() + "_dup_bd"),
				{
					"dupControllerId": dupControllerId,
					"dupFieldsMap": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_MAP'])?>,
					"dupFieldsDescriptions": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_DESCR'])?>,
					"dupCountriesInfo": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_COUNTRIES_INFO'])?>,
					"groupSummaryTitle": "<?=GetMessageJS("CRM_CONTACT_EDIT_DUP_CTRL_BANK_DETAIL_SUMMARY_TITLE")?>"
				}
			);
			var dupController = BX.CrmDupController.create(
				dupControllerId,
				{
					"serviceUrl": "/bitrix/components/bitrix/crm.contact.edit/ajax.php?&<?=bitrix_sessid_get()?>",
					"entityTypeName": "<?=CUtil::JSEscape(CCrmOwnerType::ContactName)?>",
					"form": formID,
					"submits":
					[
						"<?=CUtil::JSEscape($arResult['FORM_ID'])?>_saveAndView",
						"<?=CUtil::JSEscape($arResult['FORM_ID'])?>_saveAndAdd"
					],
					"groups":
					{
						"fullName":
						{
							"groupType": "fullName",
							"groupSummaryTitle": "<?=GetMessageJS("CRM_CONTACT_EDIT_DUP_CTRL_FULL_NAME_SUMMARY_TITLE")?>",
							"name": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['NAME_ID'])?>",
							"nameCaption": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['NAME_CAPTION_ID'])?>",
							"secondName": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['SECOND_NAME_ID'])?>",
							"secondNameCaption": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['SECOND_NAME_CAPTION_ID'])?>",
							"lastName": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['LAST_NAME_ID'])?>",
							"lastNameCaption": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['LAST_NAME_CAPTION_ID'])?>"
						},
						"email":
						{
							"groupType": "communication",
							"groupSummaryTitle": "<?=GetMessageJS("CRM_CONTACT_EDIT_DUP_CTRL_EMAIL_SUMMARY_TITLE")?>",
							"communicationType": "EMAIL",
							"editorId": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['EMAIL_EDITOR_ID'])?>",
							"editorCaption": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['EMAIL_EDITOR_CAPTION_ID'])?>"
						},
						"phone":
						{
							"groupType": "communication",
							"groupSummaryTitle": "<?=GetMessageJS("CRM_CONTACT_EDIT_DUP_CTRL_PHONE_SUMMARY_TITLE")?>",
							"communicationType": "PHONE",
							"editorId": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['PHONE_EDITOR_ID'])?>",
							"editorCaption": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['PHONE_EDITOR_CAPTION_ID'])?>"
						}
					}
				}
			);
		}
	);
</script>
<?endif;?>