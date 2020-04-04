<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?
$APPLICATION->IncludeComponent(
	"bitrix:intranet.reserve_meeting.menu",
	"",
	Array(
		"PATH_TO_MEETING_LIST" => $arResult["PATH_TO_MEETING_LIST"],
		"PATH_TO_MODIFY_MEETING" => $arResult["PATH_TO_MODIFY_MEETING"],
		"PATH_TO_MEETING" => $arResult["PATH_TO_MEETING"],
		"PATH_TO_RESERVE_MEETING" => $arResult["PATH_TO_RESERVE_MEETING"],
		"PATH_TO_SEARCH" => $arResult["PATH_TO_SEARCH"],
		"USERGROUPS_MODIFY" => $arResult["USERGROUPS_MODIFY"],
		"USERGROUPS_RESERVE" => $arResult["USERGROUPS_RESERVE"],
		"MEETING_VAR" => $arResult["ALIASES"]["meeting_id"],
		"PAGE_VAR" => $arResult["ALIASES"]["page"],
		"MEETING_ID" => 0,
		"PAGE_ID" => "list",
		"IBLOCK_ID" => $arResult["IBLOCK_ID"],
	),
	$component
);
?>

<br />

<?
$APPLICATION->IncludeComponent(
	"bitrix:intranet.reserve_meeting.list",
	"",
	Array(
		"IBLOCK_TYPE" => $arResult["IBLOCK_TYPE"],
		"IBLOCK_ID" => $arResult["IBLOCK_ID"],
		"PAGE_VAR" => $arResult["ALIASES"]["page"],
		"MEETING_VAR" => $arResult["ALIASES"]["meeting_id"],
		"PATH_TO_MEETING" => $arResult["PATH_TO_MEETING"],
		"PATH_TO_MEETING_LIST" => $arResult["PATH_TO_MEETING_LIST"],
		"PATH_TO_RESERVE_MEETING" => $arResult["PATH_TO_RESERVE_MEETING"],
		"PATH_TO_MODIFY_MEETING" => $arResult["PATH_TO_MODIFY_MEETING"],
		"SET_NAVCHAIN" => $arResult["SET_NAVCHAIN"],
		"SET_TITLE" => $arResult["SET_TITLE"],
		"USERGROUPS_MODIFY" => $arResult["USERGROUPS_MODIFY"],
		"USERGROUPS_RESERVE" => $arResult["USERGROUPS_RESERVE"],
	),
	$component
);
?>