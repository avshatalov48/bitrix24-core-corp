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

$arParams["PATH_TO_MEETING"] = Trim($arParams["PATH_TO_MEETING"]);
if (StrLen($arParams["PATH_TO_MEETING"]) <= 0)
	$arParams["PATH_TO_MEETING"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=meeting&".$arParams["MEETING_VAR"]."=#meeting_id#");

$arParams["PATH_TO_MEETING_LIST"] = Trim($arParams["PATH_TO_MEETING_LIST"]);
if (StrLen($arParams["PATH_TO_MEETING_LIST"]) <= 0)
	$arParams["PATH_TO_MEETING_LIST"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage());

$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "Y" ? "Y" : "N");
$arParams["SET_NAVCHAIN"] = ($arParams["SET_NAVCHAIN"] == "Y" ? "Y" : "N");

if (!Is_Array($arParams["USERGROUPS_MODIFY"]))
{
	if (IntVal($arParams["USERGROUPS_MODIFY"]) > 0)
		$arParams["USERGROUPS_MODIFY"] = array($arParams["USERGROUPS_MODIFY"]);
	else
		$arParams["USERGROUPS_MODIFY"] = array();
}


$arResult["FatalError"] = "";

if (!CIBlockRights::UserHasRightTo($iblockId, $iblockId, 'element_read'))
	$arResult["FatalError"] .= GetMessage("INTS_NO_IBLOCK_PERMS").".";

$arResult["ALLOWED_FIELDS"] = array(
	"ID" => array(
		"NAME" => GetMessage("INAF_F_ID"),
		"ORDERABLE" => true,
		"FILTERABLE" => true,
		"TYPE" => "int",
		"IS_FIELD" => true,
	),
	"NAME" => array(
		"NAME" => GetMessage("INAF_F_NAME"),
		"ORDERABLE" => true,
		"FILTERABLE" => true,
		"TYPE" => "string",
		"IS_FIELD" => true,
	),
	"DESCRIPTION" => array(
		"NAME" => GetMessage("INAF_F_DESCRIPTION"),
		"ORDERABLE" => false,
		"FILTERABLE" => false,
		"TYPE" => "text",
		"IS_FIELD" => true,
	),
	"UF_FLOOR" => array(
		"NAME" => GetMessage("INAF_F_FLOOR"),
		"ORDERABLE" => true,
		"FILTERABLE" => true,
		"TYPE" => "integer",
		"IS_FIELD" => false,
	),
	"UF_PLACE" => array(
		"NAME" => GetMessage("INAF_F_PLACE"),
		"ORDERABLE" => true,
		"FILTERABLE" => true,
		"TYPE" => "integer",
		"IS_FIELD" => false,
	),
	"UF_PHONE" => array(
		"NAME" => GetMessage("INAF_F_PHONE"),
		"ORDERABLE" => false,
		"FILTERABLE" => false,
		"TYPE" => "string",
		"IS_FIELD" => false,
	),
);

$arUserFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("IBLOCK_".$iblockId."_SECTION", 0, LANGUAGE_ID);

$arKeys = Array_Keys($arResult["ALLOWED_FIELDS"]);
foreach ($arKeys as $key)
{
	if (!$arResult["ALLOWED_FIELDS"][$key]["IS_FIELD"])
	{
		if (!Array_Key_Exists($key, $arUserFields))
		{
			$arFields = Array(
				"ENTITY_ID" => "IBLOCK_".$iblockId."_SECTION",
				"FIELD_NAME" => $key,
				"USER_TYPE_ID" => $arResult["ALLOWED_FIELDS"][$key]["TYPE"],
			);

			$obUserField = new CUserTypeEntity;
			$obUserField->Add($arFields);
		}
	}
}


if ($arParams["SET_TITLE"] == "Y")
	$APPLICATION->SetTitle(GetMessage("INTASK_C36_PAGE_TITLE"));

if ($arParams["SET_NAVCHAIN"] == "Y")
	$APPLICATION->AddChainItem(GetMessage("INTASK_C36_PAGE_TITLE1"), CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MEETING_LIST"], array()));

if (!$GLOBALS["USER"]->IsAuthorized())
	$arResult["FatalError"] = GetMessage("INTASK_C36_SHOULD_AUTH").". ";

if (StrLen($arResult["FatalError"]) <= 0)
{
	if (!$GLOBALS["USER"]->IsAdmin()
		&& Count(Array_Intersect($GLOBALS["USER"]->GetUserGroupArray(), $arParams["USERGROUPS_MODIFY"])) <= 0)
	{
		$arResult["FatalError"] = GetMessage("INTASK_C36_NO_PERMS2CREATE").". ";
	}
}

if (StrLen($arResult["FatalError"]) <= 0)
{
	$arMeeting = false;

	if ($meetingId > 0)
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
}

if (StrLen($arResult["FatalError"]) <= 0)
{
	$bVarsFromForm = false;
	if ($_SERVER["REQUEST_METHOD"] == "POST" && StrLen($_POST["save"]) > 0 && check_bitrix_sessid())
	{
		$errorMessage = "";

		$nameV = $_REQUEST["name"];
		$descriptionV = $_REQUEST["description"];
		$uf_floorV = IntVal($_REQUEST["uf_floor"]);
		$uf_placeV = IntVal($_REQUEST["uf_place"]);
		$uf_phoneV = $_REQUEST["uf_phone"];

		if (StrLen($nameV) <= 0)
			$errorMessage .= GetMessage("INTASK_C36_EMPTY_NAME").". ";

		if (StrLen($errorMessage) <= 0)
		{
			$sanitizer = new \CBXSanitizer();
			$sanitizer->setLevel(\CBXSanitizer::SECURE_LEVEL_LOW);
			$sanitizer->applyHtmlSpecChars(true);
			$sanitizer->deleteSanitizedTags(false);

			$arFields = array(
				"NAME" => $nameV,
				"DESCRIPTION" => $sanitizer->sanitizeHtml($descriptionV),
				"ACTIVE" => "Y",
				"IBLOCK_ID" => $iblockId,
				"IBLOCK_SECTION_ID" => 0,
				"UF_FLOOR" => $uf_floorV,
				"UF_PLACE" => $uf_placeV,
				"UF_PHONE" => $uf_phoneV,
			);

			$iblockSectionObject = new CIBlockSection;

			if ($arMeeting)
			{
				$res = $iblockSectionObject->Update($meetingId, $arFields);
			}
			else
			{
				$idTmp = $iblockSectionObject->Add($arFields);
				$res = ($idTmp > 0);
			}

			if (!$res)
				$errorMessage .= $iblockSectionObject->LAST_ERROR." ";
			else
				CIBlockSection::ReSort($iblockId);
		}

		if (StrLen($errorMessage) <= 0)
		{
			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MEETING_LIST"], array()));
		}
		else
		{
			$arResult["ErrorMessage"] .= $errorMessage;
			$bVarsFromForm = true;

			$arResult["Item"]["NAME"] = HtmlSpecialCharsbx($_REQUEST["name"]);
			$arResult["Item"]["DESCRIPTION"] = HtmlSpecialCharsbx($_REQUEST["description"]);
			$arResult["Item"]["UF_FLOOR"] = HtmlSpecialCharsbx($_REQUEST["uf_floor"]);
			$arResult["Item"]["UF_PLACE"] = HtmlSpecialCharsbx($_REQUEST["uf_place"]);
			$arResult["Item"]["UF_PHONE"] = HtmlSpecialCharsbx($_REQUEST["uf_phone"]);
		}
	}
}

if (StrLen($arResult["FatalError"]) <= 0)
{
	$arResult["MEETING"] = $arMeeting;

	if ($arParams["SET_TITLE"] == "Y")
		$APPLICATION->SetTitle($arMeeting ? GetMessage("INTASK_C36_PAGE_TITLE2").": ".$arMeeting["NAME"] : GetMessage("INTASK_C36_PAGE_TITLE"));

	if ($arParams["SET_NAVCHAIN"] == "Y")
		$APPLICATION->AddChainItem($arMeeting ? $arMeeting["NAME"] : GetMessage("INTASK_C36_PAGE_TITLE"));

	if (!$bVarsFromForm)
	{
		$arResult["Item"]["NAME"] = $arMeeting ? $arMeeting["NAME"] : "";
		$arResult["Item"]["DESCRIPTION"] = $arMeeting ? $arMeeting["DESCRIPTION"] : "";
		$arResult["Item"]["UF_FLOOR"] = $arMeeting ? $arMeeting["UF_FLOOR"] : "";
		$arResult["Item"]["UF_PLACE"] = $arMeeting ? $arMeeting["UF_PLACE"] : "";
		$arResult["Item"]["UF_PHONE"] = $arMeeting ? $arMeeting["UF_PHONE"] : "";
	}
}

//echo "<pre>".print_r($arResult, true)."</pre>";

$this->IncludeComponentTemplate();
?>