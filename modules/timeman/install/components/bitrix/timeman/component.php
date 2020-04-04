<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (CBXFeatures::IsFeatureEnabled('timeman')&&CModule::IncludeModule('timeman')&&CTimeMan::CanUse())
{
	if (abs(CTimeZone::GetOffset()) > BX_TIMEMAN_WRONG_DATE_CHECK)
	{
		$arResult['ERROR'] = 'WRONG_DATE';
		$this->IncludeComponentTemplate('error');
		return true;
	}

	$arResult['TASKS_ENABLED'] = CBXFeatures::IsFeatureEnabled('Tasks') && CModule::IncludeModule('tasks');

	$arResult['START_INFO'] = CTimeMan::GetRuntimeInfo(false);

	$obReportUser = new CUserReportFull;
	$arResult['WORK_REPORT'] = $obReportUser->GetReportData();
//echo '<pre>'; print_r($arResult['WORK_REPORT']); echo '</pre>';
	CIntranetPlanner::initScripts($arResult['START_INFO']['PLANNER']);
	$arResult['START_INFO']['PLANNER'] = $arResult['START_INFO']['PLANNER']['DATA'];

	CJSCore::Init(array('timeman'));

	$this->IncludeComponentTemplate();
	return true;
}
else
{
	return false;
}
?>