<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?
$APPLICATION->IncludeComponent(
	"bitrix:intranet.reserve_meeting.menu",
	"",
	Array(
		"PATH_TO_MEETING_LIST" => $arResult["PATH_TO_MEETING_LIST"],
		"PATH_TO_MEETING" => $arResult["PATH_TO_MEETING"],
		"PATH_TO_MODIFY_MEETING" => $arResult["PATH_TO_MODIFY_MEETING"],
		"PATH_TO_RESERVE_MEETING" => $arResult["PATH_TO_RESERVE_MEETING"],
		"PATH_TO_SEARCH" => $arResult["PATH_TO_SEARCH"],
		"USERGROUPS_MODIFY" => $arResult["USERGROUPS_MODIFY"],
		"USERGROUPS_RESERVE" => $arResult["USERGROUPS_RESERVE"],
		"MEETING_VAR" => $arResult["ALIASES"]["meeting_id"],
		"PAGE_VAR" => $arResult["ALIASES"]["page"],
		"MEETING_ID" => $arResult["VARIABLES"]["meeting_id"],
		"PAGE_ID" => "meeting",
		"IBLOCK_ID" => $arResult["IBLOCK_ID"],
	),
	$component
);
?>

<br />

<?
$APPLICATION->IncludeComponent(
	"bitrix:intranet.reserve_meeting.meeting",
	"",
	Array(
		"IBLOCK_TYPE" => $arResult["IBLOCK_TYPE"],
		"IBLOCK_ID" => $arResult["IBLOCK_ID"],
		"PAGE_VAR" => $arResult["ALIASES"]["page"],
		"MEETING_VAR" => $arResult["ALIASES"]["meeting_id"],
		"MEETING_ID" => $arResult["VARIABLES"]["meeting_id"],
		"PATH_TO_MEETING" => $arResult["PATH_TO_MEETING"],
		"PATH_TO_MEETING_LIST" => $arResult["PATH_TO_MEETING_LIST"],
		"PATH_TO_RESERVE_MEETING" => $arResult["PATH_TO_RESERVE_MEETING"],
		"PATH_TO_MODIFY_MEETING" => $arResult["PATH_TO_MODIFY_MEETING"],
		"SET_NAVCHAIN" => $arResult["SET_NAVCHAIN"],
		"SET_TITLE" => $arResult["SET_TITLE"],
		"USERGROUPS_CLEAR" => $arResult["USERGROUPS_CLEAR"],
		"WEEK_HOLIDAYS" => $arResult["WEEK_HOLIDAYS"],
		"PATH_TO_USER" => $arParams["PATH_TO_USER"],
		"PM_URL" => $arParams["PM_URL"],
		"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
		"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
		"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
		"SHOW_YEAR" => $arParams["SHOW_YEAR"],
	),
	$component
);
?>