<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("socialnetwork")):
	ShowError(GetMessage("W_SONET_IS_NOT_INSTALLED"));
	return 0;
elseif (!CModule::IncludeModule("intranet")):
	ShowError(GetMessage("W_INTRANET_IS_NOT_INSTALLED"));
	return 0;
endif;

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$GLOBALS["DBType"]."/favorites.php");

$iblockId = Trim($arParams["IBLOCK_ID"]);

$taskType = StrToLower($arParams["TASK_TYPE"]);
if (!In_Array($taskType, array("group", "user", "personal")))
	$taskType = "personal";

$ownerId = IntVal($arParams["OWNER_ID"]);
if ($ownerId <= 0)
{
	$taskType = "personal";
	$ownerId = $GLOBALS["USER"]->GetID();
}
$ownerId = IntVal($ownerId);

$arParams["TASK_VAR"] = Trim($arParams["TASK_VAR"]);
if (StrLen($arParams["TASK_VAR"]) <= 0)
	$arParams["TASK_VAR"] = "task_id";

$arParams["GROUP_VAR"] = Trim($arParams["GROUP_VAR"]);
if (StrLen($arParams["GROUP_VAR"]) <= 0)
	$arParams["GROUP_VAR"] = "group_id";

$arParams["USER_VAR"] = Trim($arParams["USER_VAR"]);
if (StrLen($arParams["USER_VAR"]) <= 0)
	$arParams["USER_VAR"] = "user_id";

$arParams["VIEW_VAR"] = Trim($arParams["VIEW_VAR"]);
if (StrLen($arParams["VIEW_VAR"]) <= 0)
	$arParams["VIEW_VAR"] = "view_id";

$arParams["ACTION_VAR"] = Trim($arParams["ACTION_VAR"]);
if (StrLen($arParams["ACTION_VAR"]) <= 0)
	$arParams["ACTION_VAR"] = "action";

if(strLen($arParams["PAGE_VAR"])<=0)
	$arParams["PAGE_VAR"] = "page";

$arParams["PATH_TO_GROUP_TASKS"] = Trim($arParams["PATH_TO_GROUP_TASKS"]);
if (StrLen($arParams["PATH_TO_GROUP_TASKS"]) <= 0)
	$arParams["PATH_TO_GROUP_TASKS"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks&".$arParams["GROUP_VAR"]."=#group_id#");
$arParams["PATH_TO_USER_TASKS"] = Trim($arParams["PATH_TO_USER_TASKS"]);
if (StrLen($arParams["PATH_TO_USER_TASKS"]) <= 0)
	$arParams["PATH_TO_USER_TASKS"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks&".$arParams["USER_VAR"]."=#user_id#");

$arParams["PATH_TO_GROUP_TASKS_TASK"] = Trim($arParams["PATH_TO_GROUP_TASKS_TASK"]);
if (StrLen($arParams["PATH_TO_GROUP_TASKS_TASK"]) <= 0)
	$arParams["PATH_TO_GROUP_TASKS_TASK"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks_task&".$arParams["GROUP_VAR"]."=#group_id#&".$arParams["TASK_VAR"]."=#task_id#&".$arParams["ACTION_VAR"]."=#action#");
$arParams["PATH_TO_USER_TASKS_TASK"] = Trim($arParams["PATH_TO_USER_TASKS_TASK"]);
if (StrLen($arParams["PATH_TO_USER_TASKS_TASK"]) <= 0)
	$arParams["PATH_TO_USER_TASKS_TASK"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_task&".$arParams["USER_VAR"]."=#user_id#&".$arParams["TASK_VAR"]."=#task_id#&".$arParams["ACTION_VAR"]."=#action#");

$arParams["PATH_TO_GROUP_TASKS_VIEW"] = Trim($arParams["PATH_TO_GROUP_TASKS_VIEW"]);
if (StrLen($arParams["PATH_TO_GROUP_TASKS_VIEW"]) <= 0)
	$arParams["PATH_TO_GROUP_TASKS_VIEW"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks_view&".$arParams["GROUP_VAR"]."=#group_id#&".$arParams["VIEW_VAR"]."=#view_id#&".$arParams["ACTION_VAR"]."=#action#");
$arParams["PATH_TO_USER_TASKS_VIEW"] = Trim($arParams["PATH_TO_USER_TASKS_VIEW"]);
if (StrLen($arParams["PATH_TO_USER_TASKS_VIEW"]) <= 0)
	$arParams["PATH_TO_USER_TASKS_VIEW"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_view&".$arParams["USER_VAR"]."=#user_id#&".$arParams["VIEW_VAR"]."=#view_id#&".$arParams["ACTION_VAR"]."=#action#");

if ($taskType == "user")
{
	$arParams["PATH_TO_TASKS"] = Str_Replace("#user_id#", "#owner_id#", $arParams["PATH_TO_USER_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = Str_Replace("#user_id#", "#owner_id#", $arParams["PATH_TO_USER_TASKS_TASK"]);
	$arParams["PATH_TO_TASKS_VIEW"] = Str_Replace("#user_id#", "#owner_id#", $arParams["PATH_TO_USER_TASKS_VIEW"]);
}
else
{
	$arParams["PATH_TO_TASKS"] = Str_Replace("#group_id#", "#owner_id#", $arParams["PATH_TO_GROUP_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = Str_Replace("#group_id#", "#owner_id#", $arParams["PATH_TO_GROUP_TASKS_TASK"]);
	$arParams["PATH_TO_TASKS_VIEW"] = Str_Replace("#group_id#", "#owner_id#", $arParams["PATH_TO_GROUP_TASKS_VIEW"]);
}

$userSettingsCategory = "IntranetTasks";
$userSettingsNamePart = "Settings_";
$userSettingsNamePartLength = StrLen($userSettingsNamePart);

if ($GLOBALS["USER"]->IsAuthorized())
{
	$arResult["FatalError"] = "";

	include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/components/bitrix/intranet.tasks/init.php");

	if (!__InTaskCheckActiveFeature($taskType, $ownerId))
		$arResult["FatalError"] .= GetMessage("INTM_TASKS_OFF").".";

	if (StrLen($arResult["FatalError"]) <= 0)
	{
		$arResult["Perms"] = __InTaskInitPerms($taskType, $ownerId);
		if (!$arResult["Perms"]["view"])
			$arResult["FatalError"] .= GetMessage("INTM_NO_SONET_PERMS").".";
	}

	if (StrLen($arResult["FatalError"]) <= 0)
	{
		$strSectionId = "";
		if (Array_Key_Exists("flt_iblock_section", $_REQUEST))
		{
			$fsId = IntVal($_REQUEST["flt_iblock_section"]);
			if ($fsId > 0)
				$strSectionId .= "flt_iblock_section=".$fsId;
		}
	}

	if (StrLen($arResult["FatalError"]) <= 0)
	{
		if ($arParams["PAGE_ID"] == "group_tasks_task" || $arParams["PAGE_ID"] == "user_tasks_task")
		{
			if (Array_Key_Exists("back_url", $_REQUEST) && StrLen($_REQUEST["back_url"]) > 0)
			{
				$arResult["Urls"]["TasksList"] = $_REQUEST["back_url"];
			}
			else
			{
				$arResult["Urls"]["TasksList"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array("owner_id" => $ownerId));
				if (StrLen($strSectionId) > 0)
				{
					$arResult["Urls"]["TasksList"] .= (StrPos($arResult["Urls"]["TasksList"], "?") === false ? "?" : "&");
					$arResult["Urls"]["TasksList"] .= $strSectionId;
				}
			}

			if ($arParams["ACTION"] == "view")
			{
				$arResult["Urls"]["EditTask"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("owner_id" => $ownerId, "task_id" => $arParams["TASK_ID"], "action" => "edit"));
				if (Array_Key_Exists("back_url", $_REQUEST) && StrLen($_REQUEST["back_url"]) > 0)
				{
					$arResult["Urls"]["EditTask"] .= (StrPos($arResult["Urls"]["EditTask"], "?") === false ? "?" : "&");
					$arResult["Urls"]["EditTask"] .= "back_url=".UrlEncode($_REQUEST["back_url"]);
				}
			}
			elseif ($arParams["ACTION"] == "edit")
			{
				$arResult["Urls"]["ViewTask"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("owner_id" => $ownerId, "task_id" => $arParams["TASK_ID"], "action" => "view"));
				if (Array_Key_Exists("back_url", $_REQUEST) && StrLen($_REQUEST["back_url"]) > 0)
				{
					$arResult["Urls"]["ViewTask"] .= (StrPos($arResult["Urls"]["ViewTask"], "?") === false ? "?" : "&");
					$arResult["Urls"]["ViewTask"] .= "back_url=".UrlEncode($_REQUEST["back_url"]);
				}
			}
		}
		elseif ($arParams["PAGE_ID"] == "group_tasks_view" || $arParams["PAGE_ID"] == "user_tasks_view")
		{
			if (Array_Key_Exists("back_url", $_REQUEST) && StrLen($_REQUEST["back_url"]) > 0)
			{
				$arResult["Urls"]["TasksList"] = $_REQUEST["back_url"];
			}
			else
			{
				$arResult["Urls"]["TasksList"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array("owner_id" => $ownerId));
				if (StrLen($strSectionId) > 0)
				{
					$arResult["Urls"]["TasksList"] .= (StrPos($arResult["Urls"]["TasksList"], "?") === false ? "?" : "&");
					$arResult["Urls"]["TasksList"] .= $strSectionId;
				}
			}
		}
		else
		{
			$arResult["Urls"]["ChangeView"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array("owner_id" => $ownerId));
			if (StrPos($arResult["Urls"]["ChangeView"], "?") === false)
				$arResult["Urls"]["ChangeView"] .= "?user_settings_id=";
			else
				$arResult["Urls"]["ChangeView"] .= "&user_settings_id=";

			$arResult["Views"] = array();
			$dbUserOptionsList = CUserOptions::GetList(
				array("ID" => "ASC"),
				array("USER_ID_EXT" => $GLOBALS["USER"]->GetID(), "CATEGORY" => $userSettingsCategory)
			);
			while ($arUserOptionTmp = $dbUserOptionsList->Fetch())
			{
				$val = UnSerialize($arUserOptionTmp["VALUE"]);

				if ($val["IBLOCK_ID"] != $iblockId || $val["TASK_TYPE"] != $taskType || $val["OWNER_ID"] != $ownerId)
					continue;

				$id = IntVal(SubStr($arUserOptionTmp["NAME"], $userSettingsNamePartLength));
				$arResult["Views"][] = array(
					"ID" => $id,
					"TITLE" => HtmlSpecialCharsbx($val["TITLE"]),
				);
			}
			if (Count($arResult["Views"]) <= 0)
				__InTaskInstallViews($iblockId, $taskType, $ownerId);

			$userSettingsId = IntVal($arParams["USER_SETTINGS_ID"]);
			if (StrLen($arParams["USER_SETTINGS_ID"]) <= 0)
			{
				$userSettingsId = IntVal($_REQUEST["user_settings_id"]);
				if (StrLen($_REQUEST["user_settings_id"]) <= 0 
					&& Array_Key_Exists("INTASK_TASKVIEW_current_view", $_SESSION)
					&& Is_Array($_SESSION["INTASK_TASKVIEW_current_view"]) 
					&& Array_Key_Exists($taskType."-".$ownerId, $_SESSION["INTASK_TASKVIEW_current_view"]))
				{
					$userSettingsId = IntVal($_SESSION["INTASK_TASKVIEW_current_view"][$taskType."-".$ownerId]);
				}

				if (!Array_Key_Exists("INTASK_TASKVIEW_current_view", $_SESSION) || !Is_Array($_SESSION["INTASK_TASKVIEW_current_view"]))
					$_SESSION["INTASK_TASKVIEW_current_view"] = array();
				$_SESSION["INTASK_TASKVIEW_current_view"][$taskType."-".$ownerId] = $userSettingsId;
			}
			$arResult["CurrentView"] = $userSettingsId;

			$strBackUrl = "back_url=".UrlEncode($GLOBALS["APPLICATION"]->GetCurPageParam("", array()));

			$arResult["Urls"]["CreateView"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_VIEW"], array("owner_id" => $ownerId, "view_id" => 0, "action" => "create"));
			$arResult["Urls"]["CreateView"] .= (StrPos($arResult["Urls"]["CreateView"], "?") === false ? "?" : "&");
			$arResult["Urls"]["CreateView"] .= $strBackUrl;

			$arResult["Urls"]["EditView"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_VIEW"], array("owner_id" => $ownerId, "view_id" => $userSettingsId, "action" => "edit"));
			$arResult["Urls"]["EditView"] .= (StrPos($arResult["Urls"]["EditView"], "?") === false ? "?" : "&");
			$arResult["Urls"]["EditView"] .= $strBackUrl;

			if ($arResult["Perms"]["create_tasks"])
			{
				$arResult["Urls"]["CreateTask"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("owner_id" => $ownerId, "task_id" => 0, "action" => "create"));
				if (StrLen($strBackUrl) > 0)
				{
					$arResult["Urls"]["CreateTask"] .= (StrPos($arResult["Urls"]["CreateTask"], "?") === false ? "?" : "&");
					$arResult["Urls"]["CreateTask"] .= $strBackUrl;
				}
				if (StrLen($strSectionId) > 0)
				{
					$arResult["Urls"]["CreateTask"] .= (StrPos($arResult["Urls"]["CreateTask"], "?") === false ? "?" : "&");
					$arResult["Urls"]["CreateTask"] .= $strSectionId;
				}
			}
			else
			{
				$arResult["Urls"]["CreateTask"] = "";
			}

			$arResult["Urls"]["DeleteView"] = $arResult["Urls"]["ChangeView"]."0&".bitrix_sessid_get()."&action=delete_view&del_view_id=".$arResult["CurrentView"];
		}
	}

	if (StrLen($arResult["FatalError"]) <= 0)
	{
		$this->IncludeComponentTemplate();
	}
}
?>