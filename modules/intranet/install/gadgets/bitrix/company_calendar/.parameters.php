<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:calendar.events.list", $arCurrentValues);
$arParameters = Array(
		"PARAMETERS"=> Array(
			"CALENDAR_TYPE"=>$arComponentProps["PARAMETERS"]["CALENDAR_TYPE"],
			"DETAIL_URL"=>$arComponentProps["PARAMETERS"]["DETAIL_URL"],
			"CACHE_TYPE"=>$arComponentProps["PARAMETERS"]["CACHE_TYPE"],
			"CACHE_TIME"=>$arComponentProps["PARAMETERS"]["CACHE_TIME"],
		),
		"USER_PARAMETERS"=> Array(
			"EVENTS_COUNT" => $arComponentProps["PARAMETERS"]["EVENTS_COUNT"],
		),
	);

$arParameters["PARAMETERS"]["DETAIL_URL"]["DEFAULT"] = "company_calendar";
$arParameters["PARAMETERS"]["DETAIL_URL"]["DEFAULT"] = "/about/calendar.php";
$arParameters["USER_PARAMETERS"]["EVENTS_COUNT"]["DEFAULT"] = "5";
?>
