<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("intranet")):
	ShowError(GetMessage("W_INTRANET_IS_NOT_INSTALLED"));
	return 0;
endif;

$arParams["PAGE_VAR"] = Trim($arParams["PAGE_VAR"]);
if ($arParams["PAGE_VAR"] == '')
	$arParams["PAGE_VAR"] = "page";

$arParams["MEETING_VAR"] = Trim($arParams["MEETING_VAR"]);
if ($arParams["MEETING_VAR"] == '')
	$arParams["MEETING_VAR"] = "meeting_id";

$arParams["PATH_TO_MEETING_LIST"] = Trim($arParams["PATH_TO_MEETING_LIST"]);
if ($arParams["PATH_TO_MEETING_LIST"] == '')
	$arParams["PATH_TO_MEETING_LIST"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage());

$arParams["PATH_TO_MEETING"] = Trim($arParams["PATH_TO_MEETING"]);
if ($arParams["PATH_TO_MEETING"] == '')
	$arParams["PATH_TO_MEETING"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=meeting&".$arParams["MEETING_VAR"]."=#meeting_id#");

$arParams["PATH_TO_MODIFY_MEETING"] = Trim($arParams["PATH_TO_MODIFY_MEETING"]);
if ($arParams["PATH_TO_MODIFY_MEETING"] == '')
	$arParams["PATH_TO_MODIFY_MEETING"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=modify_meeting&".$arParams["MEETING_VAR"]."=#meeting_id#");

$arParams["PATH_TO_RESERVE_MEETING"] = Trim($arParams["PATH_TO_RESERVE_MEETING"]);
if ($arParams["PATH_TO_RESERVE_MEETING"] == '')
	$arParams["PATH_TO_RESERVE_MEETING"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=reserve_meeting&".$arParams["MEETING_VAR"]."=#meeting_id#&".$arParams["ITEM_VAR"]."=#item_id#");

$arParams["PATH_TO_SEARCH"] = Trim($arParams["PATH_TO_SEARCH"]);
if ($arParams["PATH_TO_SEARCH"] == '')
	$arParams["PATH_TO_SEARCH"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=search");

if (!Is_Array($arParams["USERGROUPS_MODIFY"]))
{
	if (intval($arParams["USERGROUPS_MODIFY"]) > 0)
		$arParams["USERGROUPS_MODIFY"] = array($arParams["USERGROUPS_MODIFY"]);
	else
		$arParams["USERGROUPS_MODIFY"] = array();
}

if (!Is_Array($arParams["USERGROUPS_RESERVE"]))
{
	if (intval($arParams["USERGROUPS_RESERVE"]) > 0)
		$arParams["USERGROUPS_RESERVE"] = array($arParams["USERGROUPS_RESERVE"]);
	else
		$arParams["USERGROUPS_RESERVE"] = array();
}

$meetingId = intval($arParams["MEETING_ID"]);
if ($meetingId <= 0)
	$meetingId = intval($_REQUEST[$arParams["MEETING_VAR"]]);

$arResult["Page"] = Trim($arParams["PAGE_ID"]);
if ($arResult["Page"] == '')
	$arResult["Page"] = Trim($_REQUEST[$arParams["PAGE_VAR"]]);

$arResult["Urls"]["MeetingList"] = $arParams["PATH_TO_MEETING_LIST"];
$arResult["Urls"]["ModifyMeeting"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MODIFY_MEETING"], array("meeting_id" => $meetingId));
$arResult["Urls"]["CreateMeeting"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MODIFY_MEETING"], array("meeting_id" => 0));
$arResult["Urls"]["Meeting"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MEETING"], array("meeting_id" => $meetingId));
$arResult["Urls"]["Meeting"] .= (mb_strpos($arResult["Urls"]["Meeting"], "?") === false ? "?" : "&")."week_start=".UrlEncode($_REQUEST["week_start"]);

$arResult["Urls"]["ReserveMeeting"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_RESERVE_MEETING"], array("meeting_id" => $meetingId, "item_id" => 0));
$arResult["Urls"]["Search"] = $arParams["PATH_TO_SEARCH"];

$arResult["Perms"]["CanModify"] = ($GLOBALS["USER"]->IsAuthorized() 
	&& ($GLOBALS["USER"]->IsAdmin() 
		|| Count(Array_Intersect($GLOBALS["USER"]->GetUserGroupArray(), $arParams["USERGROUPS_MODIFY"])) > 0)
);

$arResult["Perms"]["CanReserve"] = ($GLOBALS["USER"]->IsAuthorized() 
	&& ($GLOBALS["USER"]->IsAdmin() 
		|| Count(Array_Intersect($GLOBALS["USER"]->GetUserGroupArray(), $arParams["USERGROUPS_RESERVE"])) > 0)
);

$this->IncludeComponentTemplate();
?>