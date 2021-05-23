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
	? GetMessage('CRM_COMPANY_EDIT_TITLE',
		array(
			'#ID#' => $elementID,
			'#TITLE#' => isset($arResult['ELEMENT']['TITLE']) ? $arResult['ELEMENT']['TITLE'] : ''
		)
	)
	: GetMessage('CRM_COMPANY_CREATE_TITLE');


$arFormButtons = array(
	'back_url' => $arResult['BACK_URL'],
	'standard_buttons' => true,
	'wizard_buttons' => false,
	'custom_html' => '<input type="hidden" name="company_id" value="'.$elementID.'"/>'
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

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'edit',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => $arFormButtons,
		'IS_NEW' => $elementID <= 0,
		'USER_FIELD_ENTITY_ID' => CCrmCompany::$sUFEntityID,
		'USER_FIELD_SERVICE_URL' => '/bitrix/components/bitrix/crm.config.fields.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
		'TITLE' => $arResult['CRM_CUSTOM_PAGE_TITLE'],
		'ENABLE_TACTILE_INTERFACE' => 'Y',
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	)
);
if($arResult['DUPLICATE_CONTROL']['ENABLED']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var formID = "form_" + "<?=CUtil::JSEscape($arResult['FORM_ID'])?>";
			var form = BX(formID);

			BX.CrmDuplicateSummaryPopup.messages =
			{
				title: "<?=GetMessageJS("CRM_COMPANY_EDIT_DUP_CTRL_SHORT_SUMMARY_TITLE")?>"
			};

			BX.CrmDuplicateWarningDialog.messages =
			{
				title: "<?=GetMessageJS("CRM_COMPANY_EDIT_DUP_CTRL_WARNING_DLG_TITLE")?>",
				acceptButtonTitle: "<?=GetMessageJS("CRM_COMPANY_EDIT_DUP_CTRL_WARNING_ACCEPT_BTN_TITLE")?>",
				cancelButtonTitle: "<?=GetMessageJS("CRM_COMPANY_EDIT_DUP_CTRL_WARNING_CANCEL_BTN_TITLE")?>"
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
					"groupSummaryTitle": "<?=GetMessageJS("CRM_COMPANY_EDIT_DUP_CTRL_REQUISITE_SUMMARY_TITLE")?>"
				}
			);
			var dupControllerBankDetail = BX.CrmDupControllerBankDetail.create(
				(formID.toLowerCase() + "_dup_bd"),
				{
					"dupControllerId": dupControllerId,
					"dupFieldsMap": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_MAP'])?>,
					"dupFieldsDescriptions": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_DESCR'])?>,
					"dupCountriesInfo": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_COUNTRIES_INFO'])?>,
					"groupSummaryTitle": "<?=GetMessageJS("CRM_COMPANY_EDIT_DUP_CTRL_BANK_DETAIL_SUMMARY_TITLE")?>"
				}
			);
			var dupController = BX.CrmDupController.create(
				dupControllerId,
				{
					"serviceUrl": "/bitrix/components/bitrix/crm.company.edit/ajax.php?&<?=bitrix_sessid_get()?>",
					"entityTypeName": "<?=CUtil::JSEscape(CCrmOwnerType::CompanyName)?>",
					"form": formID,
					"submits":
					[
						"<?=CUtil::JSEscape($arResult['FORM_ID'])?>_saveAndView",
						"<?=CUtil::JSEscape($arResult['FORM_ID'])?>_saveAndAdd"
					],
					"groups":
					{
						"title":
						{
							"groupType": "single",
							"groupSummaryTitle": "<?=GetMessageJS("CRM_COMPANY_EDIT_DUP_CTRL_TTL_SUMMARY_TITLE")?>",
							"parameterName": "TITLE",
							"element": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['TITLE_ID'])?>",
							"elementCaption": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['TITLE_CAPTION_ID'])?>"
						},
						"email":
						{
							"groupType": "communication",
							"groupSummaryTitle": "<?=GetMessageJS("CRM_COMPANY_EDIT_DUP_CTRL_EMAIL_SUMMARY_TITLE")?>",
							"communicationType": "EMAIL",
							"editorId": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['EMAIL_EDITOR_ID'])?>",
							"editorCaption": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['EMAIL_EDITOR_CAPTION_ID'])?>"
						},
						"phone":
						{
							"groupType": "communication",
							"groupSummaryTitle": "<?=GetMessageJS("CRM_COMPANY_EDIT_DUP_CTRL_PHONE_SUMMARY_TITLE")?>",
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