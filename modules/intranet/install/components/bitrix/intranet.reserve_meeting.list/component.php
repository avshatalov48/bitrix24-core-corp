<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("intranet"))
	return ShowError(GetMessage("EC_INTRANET_MODULE_NOT_INSTALLED"));
if (!CModule::IncludeModule("iblock"))
	return ShowError(GetMessage("EC_IBLOCK_MODULE_NOT_INSTALLED"));

$iblockId = Trim($arParams["IBLOCK_ID"]);

if (intval($iblockId) <= 0)
{
	return ShowError(GetMessage("EC_IBLOCK_ID_EMPTY"));
}

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

$arParams["PATH_TO_RESERVE_MEETING"] = Trim($arParams["PATH_TO_RESERVE_MEETING"]);
if ($arParams["PATH_TO_RESERVE_MEETING"] == '')
	$arParams["PATH_TO_RESERVE_MEETING"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=reserve_meeting&".$arParams["MEETING_VAR"]."=#meeting_id#&".$arParams["ITEM_VAR"]."=#item_id#");

$arParams["PATH_TO_MODIFY_MEETING"] = Trim($arParams["PATH_TO_MODIFY_MEETING"]);
if ($arParams["PATH_TO_MODIFY_MEETING"] == '')
	$arParams["PATH_TO_MODIFY_MEETING"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=modify_meeting&".$arParams["MEETING_VAR"]."=#meeting_id#");

$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "Y" ? "Y" : "N");
$arParams["SET_NAVCHAIN"] = ($arParams["SET_NAVCHAIN"] == "Y" ? "Y" : "N");

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

$arResult["FatalError"] = "";

if (!CIBlockRights::UserHasRightTo($iblockId, $iblockId, 'element_read'))
	$arResult["FatalError"] .= GetMessage("INTS_NO_IBLOCK_PERMS").".";

if ($arResult["FatalError"] == '')
{
	$deleteMeetingId = intval($_REQUEST["delete_meeting_id"]);

	if ($deleteMeetingId > 0 && check_bitrix_sessid() && $GLOBALS["USER"]->IsAuthorized() 
		&& ($GLOBALS["USER"]->IsAdmin() 
				|| Count(Array_Intersect($GLOBALS["USER"]->GetUserGroupArray(), $arParams["USERGROUPS_MODIFY"])) > 0))
	{
		$dbMeetingsList = CIBlockSection::GetList(
			array(),
			array("IBLOCK_ID" => $iblockId, "ID" => $deleteMeetingId)
		);
		if ($arMeeting = $dbMeetingsList->Fetch())
		{
			CIBlockSection::Delete($arMeeting["ID"]);
		}
	}
}

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
	$APPLICATION->AddChainItem(GetMessage("INTASK_C36_PAGE_TITLE"));

if ($arResult["FatalError"] == '')
{
	for ($i = 0; $i < 3; $i++)
	{
		$orderBy = (Array_Key_Exists("order_by_".$i, $_REQUEST) ? $_REQUEST["order_by_".$i] : $arParams["ORDER_BY_".$i]);
		$orderDir = (Array_Key_Exists("order_dir_".$i, $_REQUEST) ? $_REQUEST["order_dir_".$i] : $arParams["ORDER_DIR_".$i]);

		$orderBy = mb_strtoupper(Trim($orderBy));
		if (Array_Key_Exists($orderBy, $arResult["ALLOWED_FIELDS"]) && $arResult["ALLOWED_FIELDS"][$orderBy]["ORDERABLE"])
		{
			$arParams["ORDER_BY_".$i] = $orderBy;
			$arParams["ORDER_DIR_".$i] = mb_strtoupper(Trim($orderDir));
			if (!In_Array($arParams["ORDER_DIR_".$i], array("ASC", "DESC")))
				$arParams["ORDER_DIR_".$i] = "ASC";
		}
		else
		{
			$arParams["ORDER_BY_".$i] = "";
			$arParams["ORDER_DIR_".$i] = "";
		}
	}

	foreach ($arParams as $key => $value)
	{
		if (mb_strtoupper(mb_substr($key, 0, 4)) != "FLT_")
			continue;
		if (!Is_Array($value) && $value == '' || Is_Array($value) && Count($value) <= 0)
			continue;

		$key = mb_strtoupper(mb_substr($key, 4));

		$op = "";
		$opTmp = mb_substr($key, 0, 1);
		if (In_Array($opTmp, array("!", "<", ">")))
		{
			$op = $opTmp;
			$key = mb_substr($key, 1);
		}

		if (Array_Key_Exists($key, $arResult["ALLOWED_FIELDS"]) && $arResult["ALLOWED_FIELDS"][$key]["FILTERABLE"])
		{
			if ($arResult["ALLOWED_FIELDS"][$key]["TYPE"] == "datetime")
			{
				if ($value == "current")
					$arParams["FILTER"][$op.$key] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE));
				else
					$arParams["FILTER"][$op.$key] = $value;
			}
			elseif ($arResult["ALLOWED_FIELDS"][$key]["TYPE"] == "user")
			{
				if ($value == "current")
					$arParams["FILTER"][$op.$key] = $GLOBALS["USER"]->GetID();
				else
					$arParams["FILTER"][$op.$key] = $value;
			}
			else
			{
				$arParams["FILTER"][$op.$key] = $value;
			}
		}
	}

	foreach ($_REQUEST as $key => $value)
	{
		if (mb_strtoupper(mb_substr($key, 0, 4)) != "FLT_")
			continue;
		if (!Is_Array($value) && $value == '' || Is_Array($value) && Count($value) <= 0)
			continue;

		$key = mb_strtoupper(mb_substr($key, 4));

		$op = "";
		$opTmp = mb_substr($key, 0, 1);
		if (In_Array($opTmp, array("!", "<", ">")))
		{
			$op = $opTmp;
			$key = mb_substr($key, 1);
		}

		if (Array_Key_Exists($key, $arResult["ALLOWED_FIELDS"]) && $arResult["ALLOWED_FIELDS"][$key]["FILTERABLE"])
		{
			if ($arResult["ALLOWED_FIELDS"][$key]["TYPE"] == "datetime")
			{
				if ($value == "current")
					$arParams["FILTER"][$op.$key] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE));
				else
					$arParams["FILTER"][$op.$key] = $value;
			}
			elseif ($arResult["ALLOWED_FIELDS"][$key]["TYPE"] == "user")
			{
				if ($value == "current")
					$arParams["FILTER"][$op.$key] = $GLOBALS["USER"]->GetID();
				else
					$arParams["FILTER"][$op.$key] = $value;
			}
			else
			{
				$arParams["FILTER"][$op.$key] = $value;
			}
		}
	}
}


if ($arResult["FatalError"] == '')
{
	$arOrderBy = array();
	for ($i = 0; $i < 3; $i++)
	{
		if ($arParams["ORDER_BY_".$i] == '')
			continue;
		
		$orderBy = $arParams["ORDER_BY_".$i];

		if (Array_Key_Exists($orderBy, $arResult["ALLOWED_FIELDS"]) && $arResult["ALLOWED_FIELDS"][$orderBy]["ORDERABLE"])
		{
			$arParams["ORDER_DIR_".$i] = (mb_strtoupper($arParams["ORDER_DIR_".$i]) == "ASC" ? "ASC" : "DESC");
			$arOrderBy[$orderBy] = $arParams["ORDER_DIR_".$i];
		}
	}

	if (Count($arOrderBy) <= 0)
	{
		$arOrderBy["NAME"] = "ASC";
		$arOrderBy["ID"] = "DESC";
	}

	$arFilter = array("IBLOCK_ID" => $iblockId, "ACTIVE" => "Y");

	if (Is_Array($arParams["FILTER"]))
	{
		foreach ($arParams["FILTER"] as $key => $value)
		{
			$op = "";
			$opTmp = mb_substr($key, 0, 1);
			if (In_Array($opTmp, array("!", "<", ">")))
			{
				$op = $opTmp;
				$key = mb_substr($key, 1);
			}

			if (Array_Key_Exists($key, $arResult["ALLOWED_FIELDS"]) && $arResult["ALLOWED_FIELDS"][$key]["FILTERABLE"])
				$arFilter[$op.$key] = $value;
		}
	}
}

if ($arResult["FatalError"] == '')
{
	$arNavStartParams = array("nPageSize" => $arParams["ITEMS_COUNT"], "bShowAll" => false, "bDescPageNumbering" => false);
	$arNavigation = CDBResult::GetNavParams($arNavStartParams);

	$arSelectFields = array("IBLOCK_ID");
	foreach ($arResult["ALLOWED_FIELDS"] as $key => $value)
		$arSelectFields[] = $key;

	$arResult["MEETINGS_LIST"] = array();

	$dbMeetingsList = CIBlockSection::GetList(
		$arOrderBy,
		$arFilter,
		false,
		$arSelectFields
	);
	while ($arMeeting = $dbMeetingsList->GetNext())
	{
		$arMeeting["URI"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MEETING"], array("meeting_id" => $arMeeting["ID"]));

		$arMeeting["ACTIONS"] = array();
		$arMeeting["ACTIONS"][] = array(
			"ICON" => "",
			"TITLE" => GetMessage("INTASK_C23_GRAPH"),
			"CONTENT" => "<b>".GetMessage("INTASK_C23_GRAPH_DESCR")."</b>",
			"ONCLICK" => "setTimeout(HideThisMenuS".$arMeeting["ID"].", 900); jsUtils.Redirect([], '".CUtil::JSEscape($arMeeting["URI"])."');",
		);

		if ($GLOBALS["USER"]->IsAuthorized() 
			&& ($GLOBALS["USER"]->IsAdmin() 
				|| Count(Array_Intersect($GLOBALS["USER"]->GetUserGroupArray(), $arParams["USERGROUPS_RESERVE"])) > 0))
		{
			$arMeeting["ACTIONS"][] = array(
				"ICON" => "",
				"TITLE" => GetMessage("INTASK_C23_RESERV"),
				"CONTENT" => GetMessage("INTASK_C23_RESERV_DESCR"),
				"ONCLICK" => "setTimeout(HideThisMenuS".$arMeeting["ID"].", 900); jsUtils.Redirect([], '".CUtil::JSEscape(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_RESERVE_MEETING"], array("meeting_id" => $arMeeting["ID"], "item_id" => 0)))."');",
			);
		}

		if ($GLOBALS["USER"]->IsAuthorized() 
			&& ($GLOBALS["USER"]->IsAdmin() 
				|| Count(Array_Intersect($GLOBALS["USER"]->GetUserGroupArray(), $arParams["USERGROUPS_MODIFY"])) > 0))
		{
			$arMeeting["ACTIONS"][] = array(
				"ICON" => "",
				"TITLE" => GetMessage("INTASK_C23_EDIT"),
				"CONTENT" => GetMessage("INTASK_C23_EDIT_DESCR"),
				"ONCLICK" => "setTimeout(HideThisMenuS".$arMeeting["ID"].", 900); jsUtils.Redirect([], '".CUtil::JSEscape(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MODIFY_MEETING"], array("meeting_id" => $arMeeting["ID"])))."');",
			);

			$p = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MEETING_LIST"], array());
			$p .= (mb_strpos($p, "?") === false ? "?" : "&")."delete_meeting_id=".$arMeeting["ID"]."&".bitrix_sessid_get();

			$arMeeting["ACTIONS"][] = array(
				"ICON" => "",
				"TITLE" => GetMessage("INTASK_C23_DELETE"),
				"CONTENT" => GetMessage("INTASK_C23_DELETE_DESCR"),
				"ONCLICK" => "if(confirm('".CUtil::JSEscape(GetMessage("INTASK_C23_DELETE_CONF"))."')){jsUtils.Redirect([], '".CUtil::JSEscape($p)."')};",
			);
		}

		$arResult["MEETINGS_LIST"][] = $arMeeting;
	}
}
//echo "<pre>".print_r($arResult, true)."</pre>";

$this->IncludeComponentTemplate();
?>