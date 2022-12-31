<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arItems = $arResult;
$arResult = array();
$arResult["ITEMS"] = $arItems;

if (CModule::IncludeModule("bitrix24"))
{
	$dbApps = CBitrix24App::GetList(array(), array("ACTIVE"=>"N", "STATUS"=>"P"));
	$appsCount = $dbApps->SelectedRowsCount();
	$arResult["UNINSTALLED_PAID_APPS_COUNT"] = intval($appsCount);
}
?>

