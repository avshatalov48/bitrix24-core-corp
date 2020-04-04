<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Page\Asset;

/** @var \CCrmRequisiteFormEditorComponent $component */

global $APPLICATION;

$isExternalSearchEnabled = false;
$externalRequisiteSearchConfig = null;
if (!$arResult['NEED_CLOSE_POPUP']
	&& is_array($arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG'])
	&& isset($arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['enabled'])
	&& $arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['enabled'] === true)
{
	$isExternalSearchEnabled = true;
	$externalRequisiteSearchConfig = $arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG'];
}

$jsExts = ['date', 'popup', 'ajax', 'tooltip', 'ls'];
if ($isExternalSearchEnabled)
{
	$jsExts[] = 'applayout';
}
CJSCore::Init($jsExts);
unset($jsExts);

Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
Asset::getInstance()->addJs('/bitrix/js/crm/requisite.js');
Asset::getInstance()->addJs('/bitrix/js/crm/slider.js');
Asset::getInstance()->addCss('/bitrix/js/crm/css/slider.css');
Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');
Asset::getInstance()->addCss('/bitrix/themes/.default/crm-entity-show.css');

if(SITE_TEMPLATE_ID === 'bitrix24')
{
	Asset::getInstance()->addCss("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

$elementID = isset($arResult['ELEMENT_ID']) ? (int)$arResult['ELEMENT_ID'] : 0;
$presetID = isset($arResult['PRESET_ID']) ? (int)$arResult['PRESET_ID'] : 0;
$entityTypeID = isset($arResult['ENTITY_TYPE_ID']) ? (int)$arResult['ENTITY_TYPE_ID'] : 0;
$entityID = isset($arResult['ENTITY_ID']) ? (int)$arResult['ENTITY_ID'] : 0;

$enableFieldMasquerading = isset($arResult['ENABLE_FIELD_MASQUERADING']) && $arResult['ENABLE_FIELD_MASQUERADING'] === 'Y';
$fieldNameTemplate = isset($arResult['FIELD_NAME_TEMPLATE']) ? $arResult['FIELD_NAME_TEMPLATE'] : '';
$isPopupMode = true;

if ($arResult['ENTITY_TYPE_MNEMO'] === 'COMPANY')
{
	$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(($elementID > 0) ? 'CRM_REQUISITE_SHOW_TITLE_COMPANY' : 'CRM_REQUISITE_SHOW_NEW_TITLE_COMPANY');
}
else
{
	$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(($elementID > 0) ? 'CRM_REQUISITE_SHOW_TITLE_CONTACT' : 'CRM_REQUISITE_SHOW_NEW_TITLE_CONTACT');
}

$row = array();
foreach ($arResult['ELEMENT'] as $fName => $fValue)
{
	if(is_array($fValue))
		$row[$fName] = htmlspecialcharsEx($fValue);
	elseif(preg_match("/[;&<>\"]/", $fValue))
		$row[$fName] = htmlspecialcharsEx($fValue);
	else
		$row[$fName] = $fValue;
	$row['~'.$fName] = $fValue;
}
$arResult['ELEMENT'] = &$row;
unset($row);


$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => ($arResult['ENTITY_TYPE_MNEMO'] === 'COMPANY') ? GetMessage('CRM_TAB_1_COMPANY') : GetMessage('CRM_TAB_1_CONTACT'),
	'title' => ($arResult['ENTITY_TYPE_MNEMO'] === 'COMPANY') ? GetMessage('CRM_TAB_1_TITLE_COMPANY') : GetMessage('CRM_TAB_1_TITLE_CONTACT'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1']
);

$canEditPreset = (isset($arResult['CAN_EDIT_PRESET']) && $arResult['CAN_EDIT_PRESET'] === 'Y');

$tabsMeta = array();
foreach($arTabs as $tab)
{
	$tabId = $tab['id'];
	$tabsMeta[$tabId] = array('id' => $tabId, 'name' => $tab['name'], 'title' => $tab['title']);
	foreach($tab['fields'] as $field)
	{
		$fieldInfo = array(
			'id' => $field['id'],
			'name' => $field['name'],
			'type' => $field['type']
		);

		if($enableFieldMasquerading)
		{
			$fieldInfo['rawId'] = isset($field['rawId']) ? $field['rawId'] : $field['id'];
		}

		if(isset($field['associatedField']))
		{
			$fieldInfo['associatedField'] = $field['associatedField'];
		}

		if(isset($field['required']))
		{
			$fieldInfo['required'] = $field['required'];
		}
		if(isset($field['persistent']))
		{
			$fieldInfo['persistent'] = $field['persistent'];
		}
		if(isset($field['isRQ']) && $field['isRQ'] === true && $canEditPreset)
		{
			$fieldInfo['isRQ'] = true;
		}
		if(isset($field['inShortList']) && $field['inShortList'] === true && $canEditPreset)
		{
			$fieldInfo['inShortList'] = true;
		}

		$tabsMeta[$tabId]['fields'][$field['id']] = $fieldInfo;
	}
}

$standardButtonsTitles = array();
if (!empty($arResult['REQUISITE_REFERER']))
	$standardButtonsTitles['saveAndView']['title'] = GetMessage('CRM_REQUISITE_CUSTOM_SAVE_BUTTON_TITLE');

$tactileSettings = array('DRAG_PRIORITY' => 50, 'ENABLE_SECTION_DRAG' => 'N');
$formId = $arResult['FORM_ID'];
$wrapperId = "wrapper_".strtolower($formId);

?><div id="<?=htmlspecialcharsbx($wrapperId)?>"><?
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'edit',
	array(
		'FORM_ID' => $formId,
		'GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'TABS_META' => $tabsMeta,
		'AVAILABLE_FIELDS' => $arResult['AVAILABLE_FIELDS'],
		'USER_FIELD_ENTITY_ID' => isset($arResult['USER_FIELD_ENTITY_ID']) ? $arResult['USER_FIELD_ENTITY_ID'] : '',
		'USER_FIELD_SERVICE_URL' => '/bitrix/components/bitrix/crm.requisite.edit/uf.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
		'IS_NEW' => $elementID <= 0,
		'TITLE' => $arResult['CRM_CUSTOM_PAGE_TITLE'],
		'ENABLE_TACTILE_INTERFACE' => 'Y',
		'TACTILE_INTERFACE_SETTINGS' => $tactileSettings,
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y',
		'SHOW_FORM_TAG' => 'Y',
		'ENABLE_SECTION_CREATION' => 'N',
		'ENABLE_SECTION_EDIT' => 'Y',
		'ENABLE_SECTION_DELETE' => 'N',
		'CUSTOM_FORM_SETTINGS_COMPONENT_PATH'=> $component->getRelativePath(),
		'ENABLE_IN_SHORT_LIST_OPTION' => 'N',
		'IS_MODAL' => 'Y',
		'PREFIX' => $arResult['PREFIX'],
	)
);
?></div><?
?><script type="text/javascript">
	BX.ready(function()
	{
		BX.Crm.ExternalRequisiteDialog.messages =
		{
			searchResultNotFound: "<?=GetMessageJS('CRM_REQUISITE_SERCH_RESULT_NOT_FOUND')?>"
		};

		var formId = "<?=CUtil::JSEscape($formId)?>";
		var containerId = "container_" + formId.toLowerCase();

		BX.Crm.RequisiteEditFormManager.create(
			formId,
			{
				formId: formId,
				containerId: containerId,
				elementId: <?=$elementID?>,
				countryId: <?=CUtil::JSEscape($arResult['COUNTRY_ID'])?>,
				enableClientResolution: <?=$arResult['ENABLE_CLIENT_RESOLUTION'] ? 'true' : 'false'?>,
				enableFieldMasquerading: <?=$enableFieldMasquerading ? 'true' : 'false'?>,
				fieldNameTemplate: "<?=CUtil::JSEscape($fieldNameTemplate)?>",
				lastInForm: <?=$arResult['IS_LAST_IN_FORM'] === 'Y' ? 'true' : 'false'?>,
				externalRequisiteSearchConfig: <?=CUtil::PhpToJSObject($externalRequisiteSearchConfig)?>
			}
		);

		BX.Crm.EntityEditorToolPanel.messages =
		{
			save: "<?=GetMessageJS("CRM_REQUISITE_EDIT_BUTTON_SAVE")?>",
			cancel: "<?=GetMessageJS("CRM_REQUISITE_EDIT_BUTTON_CANCEL")?>"
		};

		BX.Crm.RequisiteSliderEditor.create(
			formId,
			{
				containerId: "<?=CUtil::JSEscape($wrapperId)?>",
				elementId: <?=$elementID?>,
				presetId: <?=$presetID?>,
				entityTypeId: <?=$entityTypeID?>,
				entityId: <?=$entityID?>,
				formId: formId,
				formSumbitUrl: "/bitrix/components/bitrix/crm.requisite.edit/popup.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
				requisiteAjaxUrl: "/bitrix/components/bitrix/crm.requisite.edit/ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
				externalContextId: "<?=CUtil::JSEscape($arResult['EXTERNAL_CONTEXT_ID'])?>",
				pseudoId: "<?=CUtil::JSEscape($arResult['PSEUDO_ID'])?>"
			}
		);
	});
</script>
<?php
if($arResult['DUPLICATE_CONTROL']['ENABLED'] && !$arResult['NEED_CLOSE_POPUP'])
{
	$entityTypeCategories = CCrmOwnerType::GetAllCategoryCaptions();

	?><script type="text/javascript">
	BX.ready(function()
	{
		var formId = "form_" + "<?=CUtil::JSEscape($arResult['FORM_ID'])?>";

		BX.CrmDuplicateSummaryPopup.messages = {
			title: "<?=GetMessageJS("CRM_REQUISITE_EDIT_DUP_CTRL_SHORT_SUMMARY_TITLE")?>"
		};

		BX.CrmDuplicateWarningDialog.messages = {
			title: "<?=GetMessageJS("CRM_REQUISITE_EDIT_DUP_CTRL_WARNING_DLG_TITLE")?>",
			acceptButtonTitle: "<?=GetMessageJS("CRM_REQUISITE_EDIT_DUP_CTRL_WARNING_ACCEPT_BTN_TITLE")?>",
			cancelButtonTitle: "<?=GetMessageJS("CRM_REQUISITE_EDIT_DUP_CTRL_WARNING_CANCEL_BTN_TITLE")?>"
		};

		BX.CrmEntityType.categoryCaptions = {
			"<?=CCrmOwnerType::LeadName?>": "<?=$entityTypeCategories[CCrmOwnerType::Lead]?>",
			"<?=CCrmOwnerType::ContactName?>": "<?=$entityTypeCategories[CCrmOwnerType::Contact]?>",
			"<?=CCrmOwnerType::CompanyName?>": "<?=$entityTypeCategories[CCrmOwnerType::Company]?>",
			"<?=CCrmOwnerType::DealName?>": "<?=$entityTypeCategories[CCrmOwnerType::Deal]?>",
			"<?=CCrmOwnerType::InvoiceName?>": "<?=$entityTypeCategories[CCrmOwnerType::Invoice]?>"
		};

		//DUPLICATE CONTROL
		var dupControllerId = (formId.toLowerCase() + "_dup");
		var dupControllerRequisite = BX.CrmDupControllerRequisite.create(
			(formId.toLowerCase() + "_dup_rq"),
			{
				"dupControllerId": dupControllerId,
				"dupFieldsMap": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_FIELDS_MAP'])?>,
				"dupFieldsDescriptions": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_FIELDS_DESCR'])?>,
				"dupCountriesInfo": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_COUNTRIES_INFO'])?>,
				"groupSummaryTitle": "<?=GetMessageJS("CRM_REQUISITE_EDIT_DUP_CTRL_REQUISITE_SUMMARY_TITLE")?>"
			}
		);
		var dupControllerBankDetail = BX.CrmDupControllerBankDetail.create(
			(formId.toLowerCase() + "_dup_bd"),
			{
				"dupControllerId": dupControllerId,
				"dupFieldsMap": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_MAP'])?>,
				"dupFieldsDescriptions": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_DESCR'])?>,
				"dupCountriesInfo": <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_COUNTRIES_INFO'])?>,
				"groupSummaryTitle": "<?=GetMessageJS("CRM_REQUISITE_EDIT_DUP_CTRL_BANK_DETAIL_SUMMARY_TITLE")?>"
			}
		);
		var dupController = BX.CrmDupController.create(
			dupControllerId,
			{
				"serviceUrl": "/bitrix/components/bitrix/crm.requisite.edit/ajax.php?&<?=bitrix_sessid_get()?>",
				"entityTypeName": "<?=CUtil::JSEscape($arResult['ENTITY_TYPE_MNEMO'])?>",
				"entityId": <?=intval($arResult['ENTITY_ID'])?>,
				"form": formId
			}
		);
	});
</script><?php
}?>