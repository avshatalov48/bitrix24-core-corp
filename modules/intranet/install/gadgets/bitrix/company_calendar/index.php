<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arGadgetParams["DETAIL_URL"] = (isset($arGadgetParams["CALENDAR_TYPE"]) ? $arGadgetParams["DETAIL_URL"]:"/about/calendar.php");
$arGadgetParams["CALENDAR_TYPE"] = (isset($arGadgetParams["CALENDAR_TYPE"]) ? $arGadgetParams["CALENDAR_TYPE"]:"company_calendar");

$arGadgetParams["EVENTS_COUNT"] = IntVal($arGadgetParams["EVENTS_COUNT"]);
$arGadgetParams["EVENTS_COUNT"] = ($arGadgetParams["EVENTS_COUNT"]>0 && $arGadgetParams["EVENTS_COUNT"]<=50 ? $arGadgetParams["EVENTS_COUNT"] : "5");
$calendar2 = COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule("calendar");
?>

<?if($calendar2):?>
	<?$APPLICATION->IncludeComponent("bitrix:calendar.events.list", "", array(
			"CALENDAR_TYPE" => $arGadgetParams["CALENDAR_TYPE"],
			"DETAIL_URL" => $arGadgetParams["DETAIL_URL"],
			"EVENTS_COUNT" => $arGadgetParams["EVENTS_COUNT"],
			"CACHE_TYPE" => $arGadgetParams["CACHE_TYPE"],
			"CACHE_TIME" => $arGadgetParams["CACHE_TIME"],
			"FUTURE_MONTH_COUNT" => 2
		),
		false,
		Array("HIDE_ICONS"=>"Y")
	);?>
<?else:?>
	<?$APPLICATION->IncludeComponent("bitrix:intranet.event_calendar", ".default", array(
			"IBLOCK_TYPE" => $arGadgetParams["IBLOCK_TYPE"],
			"IBLOCK_ID" => $arGadgetParams["IBLOCK_ID"],
			"EVENT_LIST_MODE" => "Y",
			"DETAIL_URL" => $arGadgetParams["DETAIL_URL"],
			"EVENTS_COUNT" => $arGadgetParams["EVENTS_COUNT"],
			"ALLOW_SUPERPOSE" => "N",
			"CACHE_TYPE" => $arGadgetParams["CACHE_TYPE"],
			"CACHE_TIME" => $arGadgetParams["CACHE_TIME"]
		),
		false,
		Array("HIDE_ICONS"=>"Y")
	);?>
<?endif;?>

<?if(strlen($arGadgetParams["DETAIL_URL"])>0):?>
<br />
<div align="right"><a href="<?=htmlspecialcharsbx($arGadgetParams["DETAIL_URL"])?>"><?echo GetMessage("GD_COMPANY_CALENDAR_ALL")?></a> <a href="<?=htmlspecialcharsbx($arGadgetParams["DETAIL_URL"])?>"><img width="7" height="7" border="0" src="/images/icons/arrows.gif" /></a>
<br /></div>
<?endif?>
