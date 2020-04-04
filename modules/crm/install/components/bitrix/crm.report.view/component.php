<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$requiredModules = array('crm', 'report', 'intranet', 'socialnetwork');

foreach ($requiredModules as $requiredModule)
{
	if (!CModule::IncludeModule($requiredModule))
	{
		ShowError(strtoupper($requiredModule).'_MODULE_NOT_INSTALLED');
		return 0;
	}
}

if(!CCrmCurrency::EnsureReady())
{
	ShowError(CCrmCurrency::GetLastError());
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid())
{
	global $APPLICATION;
	$reportCurrencyID = isset($_POST['crmReportCurrencyID']) ? $_POST['crmReportCurrencyID'] : '';
	if(isset($reportCurrencyID[0]))
	{
		CCrmReportHelper::SetReportCurrencyID($reportCurrencyID);
		LocalRedirect($APPLICATION->GetCurPage());
	}
}

$reportID = $arResult['REPORT_ID'] = isset($arParams['REPORT_ID']) ? intval($arParams['REPORT_ID']) : 0;
$reportData = $arResult['REPORT_DATA'] = CCrmReportManager::getReportData($reportID);
$reportOwnerID = $arResult['REPORT_OWNER_ID'] = $reportData && isset($reportData['OWNER_ID']) ? $reportData['OWNER_ID'] : '';
$arResult['REPORT_HELPER_CLASS'] = $reportOwnerID !== '' ? CCrmReportManager::getOwnerHelperClassName($reportOwnerID) : '';
$arResult['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$this->IncludeComponentTemplate();
?>