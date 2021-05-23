<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CBXFeatures::IsFeatureEnabled('timeman') || !CModule::IncludeModule('timeman'))
	return;

// maybe we should cache GetAccess data?
$arResult['arAccessUsers'] = CTimeMan::GetAccess();

if (count($arResult['arAccessUsers']['READ']) > 0)
{
	CUtil::InitJSCore(array('timeman', 'date'));

	$arUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('USER', 0, LANGUAGE_ID);
	$arResult['UF_DEPARTMENT_field'] = $arUserFields['UF_DEPARTMENT'];
	$arResult['UF_DEPARTMENT_field']['FIELD_NAME'] = 'department';
	$arResult['UF_DEPARTMENT_field']['MULTIPLE'] = 'N';
	$arResult['UF_DEPARTMENT_field']['SETTINGS']['LIST_HEIGHT'] = 1;

	$arResult['TASKS_AVAILABLE'] = \Bitrix\Main\ModuleManager::isModuleInstalled('tasks');

	$this->IncludeComponentTemplate();
}
?>