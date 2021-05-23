<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/** @global CMain $APPLICATION */
global $APPLICATION;

$arResult['FORM_ID'] = 'CRM_TRACKER_CONFIG';
$arResult['PATH_TO_CONFIGS_INDEX'] = '/crm/configs/';
$arResult['BACK_URL'] = $arResult['PATH_TO_CONFIGS_INDEX'];
$arResult['PATH_TO_TRACKER_INDEX'] = $APPLICATION->GetCurPage();
$arResult['FIELDS'] = array();
$arResult['DATA'] = array();


if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$arResult['FORM_ID'].'_active_tab']) && check_bitrix_sessid())
{
	$companyType = \Bitrix\Crm\Rest\CCrmExternalChannelImportPreset::PRESET_PERSON_TYPE_COMPANY;
	$personType = \Bitrix\Crm\Rest\CCrmExternalChannelImportPreset::PRESET_PERSON_TYPE_PERSON;



	$companyPresetId = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get($companyType);
	$contactPresetId = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get($personType);

	if($companyPresetId <=0 || $contactPresetId <=0 )
	{
		$arResult['DATA'] = array(
				$companyType => $companyPresetId,
				$personType => $contactPresetId
		);

		ShowError(GetMessage("CRM_FIELDS_EMPTY"));
	}
	else
	{
		\Bitrix\Crm\Rest\CCrmExternalChannelImportPreset::setOption(array(
				$companyType => $companyPresetId,
				$personType => $contactPresetId
		));
	}
}

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('currency'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY'));
	return;
}

global $APPLICATION, $USER;
$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

if(empty($arResult['DATA']))
	$arResult['DATA'] = \Bitrix\Crm\Rest\CCrmExternalChannelImportPreset::getList();

$result = \Bitrix\Crm\PresetTable::getList(array(
		'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
		'select' => array('ID', 'NAME')
));

$arResult['PRESETS_LIST'][] = '';
while($presetItem = $result->fetch())
	$arResult['PRESETS_LIST'][$presetItem['ID']] = $presetItem['NAME'];


$this->IncludeComponentTemplate();
