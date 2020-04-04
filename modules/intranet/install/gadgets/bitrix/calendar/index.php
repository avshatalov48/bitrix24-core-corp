<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arGadgetParams["EVENTS_COUNT"] = intval($arGadgetParams["EVENTS_COUNT"]);
$arGadgetParams["EVENTS_COUNT"] = ($arGadgetParams["EVENTS_COUNT"] > 0 && $arGadgetParams["EVENTS_COUNT"] < 30 ? $arGadgetParams["EVENTS_COUNT"] : 5);
$arGadgetParams["CALENDAR_URL"] = CComponentEngine::MakePathFromTemplate(($arGadgetParams["CALENDAR_URL"] ? $arGadgetParams["CALENDAR_URL"] : "/company/personal/user/#user_id#/calendar/"), array("user_id" => $GLOBALS["USER"]->GetID()));
$arGadgetParams["DETAIL_URL"] = ($arGadgetParams["DETAIL_URL"] ? $arGadgetParams["DETAIL_URL"] : "/company/personal/user/#user_id#/calendar/");
$calendar2 = COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule("calendar");
?>

<?if($calendar2):?>
<?$APPLICATION->IncludeComponent("bitrix:calendar.events.list", "", array(
			"CALENDAR_TYPE" => 'user',
			"B_CUR_USER_LIST" => "Y",
			"DETAIL_URL" => $arGadgetParams["DETAIL_URL"],
			"EVENTS_COUNT" => $arGadgetParams["EVENTS_COUNT"],
			"CACHE_TYPE" => $arGadgetParams["CACHE_TYPE"],
			"CACHE_TIME" => $arGadgetParams["CACHE_TIME"],
			"FUTURE_MONTH_COUNT" => 2
		),
		false,
		Array("HIDE_ICONS" => "Y")
	);?>
<?else:?>
<?$APPLICATION->IncludeComponent("bitrix:intranet.event_calendar", ".default", array(
			"IBLOCK_TYPE" => $arGadgetParams["IBLOCK_TYPE"],
			"IBLOCK_ID" => $arGadgetParams["IBLOCK_ID"],
			"EVENT_LIST_MODE" => "Y",
			"B_CUR_USER_LIST" => "Y",
			"DETAIL_URL" => $arGadgetParams["DETAIL_URL"],
			"EVENTS_COUNT" => $arGadgetParams["EVENTS_COUNT"],
			"WORK_TIME_START" => "9",
			"WORK_TIME_END" => "19",
			"ALLOW_SUPERPOSE" => "N",
			"CACHE_TYPE" => $arGadgetParams["CACHE_TYPE"],
			"CACHE_TIME" => $arGadgetParams["CACHE_TIME"]
		),
		false,
		Array("HIDE_ICONS" => "Y")
	);?>
<?endif;?>

<br/>
<div align="right"><a href="<?=$arGadgetParams["CALENDAR_URL"]?>"><?echo GetMessage("GD_CALENDAR_ALL")?></a> <a
	href="<?=$arGadgetParams["CALENDAR_URL"]?>"><img width="7" height="7" border="0" src="/images/icons/arrows.gif"/></a>
	<br/>
</div>
