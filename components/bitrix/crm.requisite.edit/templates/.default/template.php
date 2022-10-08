<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Page\Asset;

/** @var \CCrmRequisiteFormEditorComponent $component */

global $APPLICATION;

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

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

$jsExts = ['date', 'popup', 'ajax', 'tooltip'];
if ($isExternalSearchEnabled)
{
	$jsExts[] = 'applayout';
}
CJSCore::Init($jsExts);
unset($jsExts);

Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
Asset::getInstance()->addJs('/bitrix/js/crm/requisite.js');

$elementID = isset($arResult['ELEMENT_ID']) ? (int)$arResult['ELEMENT_ID'] : 0;
$pseudoID = isset($arResult['PSEUDO_ID']) ? (string)$arResult['PSEUDO_ID'] : 'n0';
$enableFieldMasquerading = isset($arResult['ENABLE_FIELD_MASQUERADING']) && $arResult['ENABLE_FIELD_MASQUERADING'] === 'Y';
$fieldNameTemplate = isset($arResult['FIELD_NAME_TEMPLATE']) ? $arResult['FIELD_NAME_TEMPLATE'] : '';
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

$buttons = null;
if ($arResult['POPUP_MODE'] === 'N' && $arResult['INNER_FORM_MODE'] === 'N')
{
	$buttons = array(
		'standard_buttons' => true,
		'standard_buttons_titles' => $standardButtonsTitles
	);
}

if($arResult['INNER_FORM_MODE'] === 'Y')
{
	?><div class="crm-offer-requisite-form-wrap"><?
}


if($arResult['INNER_FORM_MODE'] === 'Y')
{
	$tactileSettings = array('ENABLE_FIELD_DRAG' => 'N', 'ENABLE_SECTION_DRAG' => 'N');
}
else
{
	$tactileSettings = array('DRAG_PRIORITY' => 50, 'ENABLE_SECTION_DRAG' => 'N');
}

$formId = $arResult['FORM_ID'];
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
		'BUTTONS' => $buttons,
		'IS_NEW' => $elementID <= 0,
		'TITLE' => (($arResult['POPUP_MODE'] === 'Y' || $arResult['INNER_FORM_MODE'] === 'Y') ? '' : $arResult['CRM_CUSTOM_PAGE_TITLE']),
		'ENABLE_TACTILE_INTERFACE' => 'Y',
		'TACTILE_INTERFACE_SETTINGS' => $tactileSettings,
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y',
		'SHOW_FORM_TAG' => $arResult['INNER_FORM_MODE'] === 'N' ? 'Y' : 'N',
		'ENABLE_SECTION_CREATION' => 'N',
		'ENABLE_SECTION_EDIT' => $arResult['POPUP_MODE'] === 'N' ? 'Y' : 'N',
		'ENABLE_SECTION_DELETE' => 'N',
		'CUSTOM_FORM_SETTINGS_COMPONENT_PATH'=> $component->getRelativePath(),
		/*'ENABLE_IN_SHORT_LIST_OPTION' => 'Y',*/
		'IS_MODAL' => $arResult['POPUP_MODE'],
		'PREFIX' => $arResult['PREFIX'],
	)
);

if($arResult['INNER_FORM_MODE'] === 'Y')
{
	?></div><?
}
?><script type="text/javascript">
	BX.ready(function()
	{
		BX.Crm.ExternalRequisiteDialog.messages =
		{
			searchResultNotFound: "<?=GetMessageJS('CRM_REQUISITE_SERCH_RESULT_NOT_FOUND')?>"
		};

		var formId = "<?=CUtil::JSEscape($formId)?>";
		var containerId = "container_" + formId.toLowerCase();
		var requisiteEditFormParams = {
			formId: formId,
			containerId: containerId,
			elementId: <?=$elementID?>,
			pseudoId: "<?=$pseudoID?>",
			countryId: <?=CUtil::JSEscape($arResult['COUNTRY_ID'])?>,
			enableClientResolution: <?=$arResult['ENABLE_CLIENT_RESOLUTION'] ? 'true' : 'false'?>,
			enableFieldMasquerading: <?=$enableFieldMasquerading ? 'true' : 'false'?>,
			fieldNameTemplate: "<?=CUtil::JSEscape($fieldNameTemplate)?>",
			lastInForm: <?=$arResult['IS_LAST_IN_FORM'] === 'Y' ? 'true' : 'false'?>,
			externalRequisiteSearchConfig: <?=CUtil::PhpToJSObject($externalRequisiteSearchConfig)?>,
			features: <?php echo CUtil::PhpToJSObject(is_array($arResult['FEATURES']) ? $arResult['FEATURES'] : []); ?>
		};
		BX.Crm.RequisiteEditFormManager.create(formId, requisiteEditFormParams);
	});
</script>
<?php
if($arResult['POPUP_MODE'] === 'Y' && $arResult['DUPLICATE_CONTROL']['ENABLED'] && !$arResult['NEED_CLOSE_POPUP'])
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