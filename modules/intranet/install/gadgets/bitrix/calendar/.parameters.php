<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:calendar.events.list", $arCurrentValues);
$arParameters = Array(
		"PARAMETERS"=> Array(
			"DETAIL_URL" => $arComponentProps["PARAMETERS"]["DETAIL_URL"],
			"CACHE_TYPE" => $arComponentProps["PARAMETERS"]["CACHE_TYPE"],
			"CACHE_TIME" => $arComponentProps["PARAMETERS"]["CACHE_TIME"],
			"CALENDAR_URL"=> Array(
				"NAME" => GetMessage("GD_CALENDAR_P_URL"),
				"DEFAULT" => "/company/personal/user/#user_id#/calendar/",
				"TYPE" => "STRING"
				)
		),
		"USER_PARAMETERS"=> Array(
			"EVENTS_COUNT" => $arComponentProps["PARAMETERS"]["EVENTS_COUNT"],
		),
	);

$arParameters["PARAMETERS"]["DETAIL_URL"]["DEFAULT"] = "/company/personal/user/#user_id#/calendar/";

?>
