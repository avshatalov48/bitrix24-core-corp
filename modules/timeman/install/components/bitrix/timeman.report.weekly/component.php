<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CBXFeatures::IsFeatureEnabled('timeman') || !CModule::IncludeModule('timeman'))
	return;

// maybe we should cache GetAccess data?
$arResult['arAccessUsers'] = CTimeMan::GetAccess();
$arResult['arAccessUsers2'] = CTimeMan::GetAccessSettings();
$arResult['arDirectUsers'] = CTimeMan::GetDirectAccess();
if (count($arResult['arAccessUsers']['READ']) > 0)
{
	CUtil::InitJSCore(array('timeman'));

	$arUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('USER', 0, LANGUAGE_ID);
	$arResult['TASKS_ENABLED'] = CBXFeatures::IsFeatureEnabled('Tasks') && CModule::IncludeModule('tasks');
	$arResult['UF_DEPARTMENT_field'] = $arUserFields['UF_DEPARTMENT'];
	$arResult['UF_DEPARTMENT_field']['FIELD_NAME'] = 'department';
	$arResult['UF_DEPARTMENT_field']['MULTIPLE'] = 'N';
	$arResult['UF_DEPARTMENT_field']['SETTINGS']['LIST_HEIGHT'] = 1;
	$arResult['SHOW_ALL'] = "Y";
	$arResult['DEPARTMENT_ID'] = "";
	if($arResult['arDirectUsers'])
	{
		$arResult['SHOW_ALL'] = CUserOptions::GetOption("timeman.report.weekly", "show_all", "Y", $USER->GetID());
		$arResult['DEPARTMENT_ID'] = CUserOptions::GetOption("timeman.report.weekly", "department_id", "", $USER->GetID());
	}
	$this->IncludeComponentTemplate();
}
?>