<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("intranet"))
	return ShowError(GetMessage("EC_INTRANET_MODULE_NOT_INSTALLED"));
if (!CModule::IncludeModule("iblock"))
	return ShowError(GetMessage("EC_IBLOCK_MODULE_NOT_INSTALLED"));

$iblockId = IntVal($arParams["IBLOCK_ID"]);

$arParams["PAGE_VAR"] = Trim($arParams["PAGE_VAR"]);
if (StrLen($arParams["PAGE_VAR"]) <= 0)
	$arParams["PAGE_VAR"] = "page";

$arParams["MEETING_VAR"] = Trim($arParams["MEETING_VAR"]);
if (StrLen($arParams["MEETING_VAR"]) <= 0)
	$arParams["MEETING_VAR"] = "meeting_id";

$meetingId = IntVal($arParams["MEETING_ID"]);
if ($meetingId <= 0)
	$meetingId = IntVal($_REQUEST[$arParams["MEETING_VAR"]]);

$arParams["ITEM_VAR"] = Trim($arParams["ITEM_VAR"]);
if (StrLen($arParams["ITEM_VAR"]) <= 0)
	$arParams["ITEM_VAR"] = "item_id";

$itemId = IntVal($arParams["ITEM_ID"]);
if ($itemId <= 0)
	$itemId = IntVal($_REQUEST[$arParams["ITEM_VAR"]]);

$arParams["PATH_TO_MEETING"] = Trim($arParams["PATH_TO_MEETING"]);
if (StrLen($arParams["PATH_TO_MEETING"]) <= 0)
	$arParams["PATH_TO_MEETING"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=meeting&".$arParams["MEETING_VAR"]."=#meeting_id#");

$arParams["PATH_TO_MEETING_LIST"] = Trim($arParams["PATH_TO_MEETING_LIST"]);
if (StrLen($arParams["PATH_TO_MEETING_LIST"]) <= 0)
	$arParams["PATH_TO_MEETING_LIST"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage());

$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "Y" ? "Y" : "N");
$arParams["SET_NAVCHAIN"] = ($arParams["SET_NAVCHAIN"] == "Y" ? "Y" : "N");

$arParams['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'] ? $arParams['NAME_TEMPLATE'] : CSite::GetNameFormat(false);
$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;

$arParams['DATE_TIME_FORMAT'] = $arParams['DATE_TIME_FORMAT'] ?: 'FULL';

$arResult["FatalError"] = "";

if (!CIBlockRights::UserHasRightTo($iblockId, $iblockId, 'element_read'))
	$arResult["FatalError"] .= GetMessage("INTS_NO_IBLOCK_PERMS").".";

include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/components/bitrix/intranet.reserve_meeting/init.php");

$ar = __IRM_InitReservation($iblockId);
$arResult["ALLOWED_FIELDS"] = $ar["ALLOWED_FIELDS"];
$arResult["ALLOWED_ITEM_PROPERTIES"] = $ar["ALLOWED_ITEM_PROPERTIES"];

if ($arParams["SET_TITLE"] == "Y")
	$APPLICATION->SetTitle(GetMessage("INTASK_C36_PAGE_TITLE"));

if ($arParams["SET_NAVCHAIN"] == "Y")
	$APPLICATION->AddChainItem(GetMessage("INTASK_C36_PAGE_TITLE1"), CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MEETING_LIST"], array()));

if (StrLen($arResult["FatalError"]) <= 0)
{
	$arSelectFields = array("IBLOCK_ID");
	foreach ($arResult["ALLOWED_FIELDS"] as $key => $value)
		$arSelectFields[] = $key;

	$dbMeeting = CIBlockSection::GetList(
		array(),
		array("ID" => $meetingId, "ACTIVE" => "Y", "IBLOCK_ID" => $iblockId),
		false,
		$arSelectFields
	);
	$arMeeting = $dbMeeting->GetNext();

	if (!$arMeeting)
		$arResult["FatalError"] = GetMessage("INAF_MEETING_NOT_FOUND")." ";
}

if (StrLen($arResult["FatalError"]) <= 0)
{
	$arResult["MEETING"] = $arMeeting;

	if ($arParams["SET_TITLE"] == "Y")
		$APPLICATION->SetTitle(GetMessage("INTASK_C36_PAGE_TITLE").": ".$arMeeting["NAME"]);

	if ($arParams["SET_NAVCHAIN"] == "Y")
		$APPLICATION->AddChainItem($arMeeting["NAME"], CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MEETING"], array("meeting_id" => $meetingId)));

	$arSelectFields = array("ID", "NAME", "IBLOCK_ID", "CREATED_BY", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO", "DETAIL_TEXT");
	foreach ($arResult["ALLOWED_ITEM_PROPERTIES"] as $key => $value)
		$arSelectFields[] = "PROPERTY_".$key;

	$dbElements = CIBlockElement::GetList(
		array(),
		array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $iblockId,
			"SECTION_ID" => $meetingId,
			"ID" => $itemId,
		),
		false,
		false,
		$arSelectFields
	);
	
	$arElement = $dbElements->GetNext();

	if (!$arElement)
		$arResult["FatalError"] = GetMessage("INAF_ITEM_NOT_FOUND")." ";
}

if (StrLen($arResult["FatalError"]) <= 0)
{
	$arResult["ITEM"] = $arElement;

	$arResult["ITEM"]["CREATED_BY_NAME"] = "-";
	$dbUser = CUser::GetByID($arElement["CREATED_BY"]);
	if ($arUser = $dbUser->GetNext())
	{
		$arResult["ITEM"]["CREATED_BY_ID"] =  $arUser["ID"];
		$arResult["ITEM"]["CREATED_BY_NAME"] = CUser::FormatName($arParams['NAME_TEMPLATE_WO_NOBR'], $arUser, $bUseLogin);
		$arResult["ITEM"]["CREATED_BY_FIRST_NAME"] = $arUser["NAME"];
		$arResult["ITEM"]["CREATED_BY_LAST_NAME"] = $arUser["LAST_NAME"];
		$arResult["ITEM"]["CREATED_BY_SECOND_NAME"] = $arUser["SECOND_NAME"];
		$arResult["ITEM"]["CREATED_BY_LOGIN"] = $arUser["LOGIN"];
	}

	if ($arResult["ITEM"]["PROPERTY_PERIOD_TYPE_VALUE"] != "NONE")
	{
		$arResult["ITEM"]["DATE_ACTIVE_TO_FINISH"] = $arResult["ITEM"]["DATE_ACTIVE_TO"];
		$arResult["ITEM"]["DATE_ACTIVE_TO"] = Date(
			$GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME),
			MakeTimeStamp($arResult["ITEM"]["DATE_ACTIVE_FROM"], FORMAT_DATETIME) + $arResult["ITEM"]["PROPERTY_EVENT_LENGTH_VALUE"]
		);
	}

	if ($arParams["SET_TITLE"] == "Y")
		$APPLICATION->SetTitle(GetMessage("INTASK_C36_PAGE_TITLE").": ".$arElement["NAME"]);

	if ($arParams["SET_NAVCHAIN"] == "Y")
		$APPLICATION->AddChainItem($arElement["NAME"]);
}

//echo "<pre>".print_r($arResult, true)."</pre>";

$this->IncludeComponentTemplate();
?>