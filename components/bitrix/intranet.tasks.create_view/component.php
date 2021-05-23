<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("iblock"))
	return ShowError(GetMessage("EC_IBLOCK_MODULE_NOT_INSTALLED"));
if (!CModule::IncludeModule("socialnetwork"))
	return ShowError(GetMessage("EC_SONET_MODULE_NOT_INSTALLED"));

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$GLOBALS["DBType"]."/favorites.php");

$iblockId = Trim($arParams["IBLOCK_ID"]);

$taskType = StrToLower($arParams["TASK_TYPE"]);
if (!in_array($taskType, array("group", "user")))
	$taskType = "user";

$ownerId = IntVal($arParams["OWNER_ID"]);
if ($ownerId <= 0)
{
	$taskType = "user";
	$ownerId = $GLOBALS["USER"]->GetID();
}
$ownerId = IntVal($ownerId);

$arParams["TASK_VAR"] = Trim($arParams["TASK_VAR"]);
if (StrLen($arParams["TASK_VAR"]) <= 0)
	$arParams["TASK_VAR"] = "task_id";

$arParams["GROUP_VAR"] = Trim($arParams["GROUP_VAR"]);
if (StrLen($arParams["GROUP_VAR"]) <= 0)
	$arParams["GROUP_VAR"] = "group_id";

$arParams["VIEW_VAR"] = Trim($arParams["VIEW_VAR"]);
if (StrLen($arParams["VIEW_VAR"]) <= 0)
	$arParams["VIEW_VAR"] = "view_id";

$arParams["ACTION_VAR"] = Trim($arParams["ACTION_VAR"]);
if (StrLen($arParams["ACTION_VAR"]) <= 0)
	$arParams["ACTION_VAR"] = "action";

if(strLen($arParams["PAGE_VAR"])<=0)
	$arParams["PAGE_VAR"] = "page";

$viewId = IntVal($arParams["VIEW_ID"]);
if ($viewId <= 0)
	$action = "create";
else
	$action = "edit";

$userSettingsCategory = "IntranetTasks";
$userSettingsNamePart = "Settings_";
$userSettingsNamePartLength = StrLen($userSettingsNamePart);

$arParams["PATH_TO_GROUP_TASKS"] = Trim($arParams["PATH_TO_GROUP_TASKS"]);
if (StrLen($arParams["PATH_TO_GROUP_TASKS"]) <= 0)
	$arParams["PATH_TO_GROUP_TASKS"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks&".$arParams["GROUP_VAR"]."=#group_id#");
$arParams["PATH_TO_USER_TASKS"] = Trim($arParams["PATH_TO_USER_TASKS"]);
if (StrLen($arParams["PATH_TO_USER_TASKS"]) <= 0)
	$arParams["PATH_TO_USER_TASKS"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks&".$arParams["USER_VAR"]."=#user_id#");

$arParams["PATH_TO_GROUP_TASKS_VIEW"] = Trim($arParams["PATH_TO_GROUP_TASKS_VIEW"]);
if (StrLen($arParams["PATH_TO_GROUP_TASKS_VIEW"]) <= 0)
	$arParams["PATH_TO_GROUP_TASKS_VIEW"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks_view&".$arParams["GROUP_VAR"]."=#group_id#&".$arParams["VIEW_VAR"]."=#view_id#&".$arParams["ACTION_VAR"]."=#action#");
$arParams["PATH_TO_USER_TASKS_VIEW"] = Trim($arParams["PATH_TO_USER_TASKS_VIEW"]);
if (StrLen($arParams["PATH_TO_USER_TASKS_VIEW"]) <= 0)
	$arParams["PATH_TO_USER_TASKS_VIEW"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_view&".$arParams["USER_VAR"]."=#user_id#&".$arParams["VIEW_VAR"]."=#view_id#&".$arParams["ACTION_VAR"]."=#action#");

if ($taskType == "user")
{
	$arParams["PATH_TO_TASKS"] = Str_Replace("#user_id#", "#owner_id#", $arParams["PATH_TO_USER_TASKS"]);
	$arParams["PATH_TO_TASKS_VIEW"] = Str_Replace("#user_id#", "#owner_id#", $arParams["PATH_TO_USER_TASKS_VIEW"]);
}
else
{
	$arParams["PATH_TO_TASKS"] = Str_Replace("#group_id#", "#owner_id#", $arParams["PATH_TO_GROUP_TASKS"]);
	$arParams["PATH_TO_TASKS_VIEW"] = Str_Replace("#group_id#", "#owner_id#", $arParams["PATH_TO_GROUP_TASKS_VIEW"]);
}

if (!$GLOBALS["USER"]->IsAuthorized())
{	
	$arResult["NEED_AUTH"] = "Y";
}
else
{
	$arResult["FatalError"] = "";

	$arParams["TASK_TYPE"] = $taskType;
	$arParams["OWNER_ID"] = $ownerId;

	include($_SERVER['DOCUMENT_ROOT']."/bitrix/components/bitrix/intranet.tasks/init.php");

	$iblockPerm = CIBlock::GetPermission($iblockId);
	if ($iblockPerm < "R")
		$arResult["FatalError"] .= GetMessage("INTV_NO_IBLOCK_PERMS").".";

	if (StrLen($arResult["FatalError"]) <= 0)
	{
		if (!__InTaskCheckActiveFeature($taskType, $ownerId))
			$arResult["FatalError"] .= GetMessage("INTV_TASKS_OFF").".";
	}

	if (StrLen($arResult["FatalError"]) <= 0)
	{
		$arResult["Perms"] = __InTaskInitPerms($taskType, $ownerId);
		if (!$arResult["Perms"]["view"])
			$arResult["FatalError"] .= GetMessage("INTV_NO_SONET_PERMS").".";
	}

	if (StrLen($arResult["FatalError"]) <= 0)
	{
		$arResult["TaskFields"] = __IntaskInitTaskFields($iblockId, $taskType, $ownerId, $arParams["TASKS_FIELDS_SHOW"]);
		if (!$arResult["TaskFields"] || !Is_Array($arResult["TaskFields"]) || Count($arResult["TaskFields"]) <= 0)
			$arResult["FatalError"] = GetMessage("INTV_INTERNAL_ERROR").".";
	}

	if (StrLen($arResult["FatalError"]) <= 0)
	{
		$arUserTemplatesList = CComponentUtil::GetTemplatesList("bitrix:intranet.tasks.view", SITE_TEMPLATE_ID);

		$userTemplateId = Trim($_REQUEST["user_template_id"]);
		$userSettingsId = (($action == "edit") ? $viewId : IntVal($_REQUEST["user_settings_id"]));

		if (StrLen($userTemplateId) > 0)
		{
			$bCorrectTemplate = false;
			foreach ($arUserTemplatesList as $arUserTemplate)
			{
				if ($arUserTemplate["NAME"] == $userTemplateId)
				{
					$bCorrectTemplate = true;
					break;
				}
			}

			if (!$bCorrectTemplate)
				$userTemplateId = "";
		}

		$arUserSettings = false;
		if ($userSettingsId > 0)
		{
			$arUserSettings = CUserOptions::GetOption($userSettingsCategory, $userSettingsNamePart.$userSettingsId, false, $GLOBALS["USER"]->GetID());
			if ($arUserSettings == false)
			{
				$userSettingsId = 0;
			}
			else
			{
				$userTemplateId = $arUserSettings["TEMPLATE"];

				if (StrLen($userTemplateId) > 0)
				{
					$bCorrectTemplate = false;
					foreach ($arUserTemplatesList as $arUserTemplate)
					{
						if ($arUserTemplate["NAME"] == $userTemplateId)
						{
							$bCorrectTemplate = true;
							break;
						}
					}

					if (!$bCorrectTemplate)
					{
						$arUserSettings = false;
						$userSettingsId = 0;
						$userTemplateId = "";
					}
				}
				else
				{
					$arUserSettings = false;
					$userSettingsId = 0;
				}

				if ($userSettingsId > 0)
				{
					if ($arUserSettings["IBLOCK_ID"] != $iblockId || $arUserSettings["TASK_TYPE"] != $taskType || $arUserSettings["OWNER_ID"] != $ownerId)
					{
						$userSettingsId = 0;
						$userTemplateId = "";
						$arUserSettings = false;
					}
				}
			}
		}

		if ($userSettingsId <= 0)
		{
			$viewId = 0;
			$action = "create";
		}

		$arResult["Perms"]["CanModifyCommon"] =	($taskType == 'user' && CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), SONET_ENTITY_USER, $ownerId, "tasks", 'modify_common_views') || $taskType == 'group' && CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), SONET_ENTITY_GROUP, $ownerId, "tasks", 'modify_common_views'));

		if ($action == "edit" && $userSettingsId > 0 && $arUserSettings["COMMON"] != "N")
		{
			if (!$arResult["Perms"]["CanModifyCommon"])
			{
				$viewId = 0;
				$action = "create";
			}
		}

		if ($arParams["SET_TITLE"] == "Y")
		{
			if ($action == "create")
				$APPLICATION->SetTitle(GetMessage("INTV_CREATE_TITLE"));
			else
				$APPLICATION->SetTitle(Str_Replace("#NAME#", $arUserTemplate["NAME"], GetMessage("INTV_EDIT_TITLE")));
		}

		$arResult["MODE"] = $action;

		if (StrLen($userTemplateId) > 0)
		{
			$arResult["ShowStep"] = 2;
			$arResult["UserSettings"] = $arUserSettings;

			if ($_SERVER["REQUEST_METHOD"] == "POST" && StrLen($_POST["save"]) > 0 && check_bitrix_sessid())
			{
				if (!array_key_exists("SHOW_COLUMN", $_POST) || !is_array($_POST["SHOW_COLUMN"]) || Count($_POST["SHOW_COLUMN"]) <= 0)
				{
					$_POST["SHOW_COLUMN"] = array();
					foreach ($arResult["TaskFields"] as $key => $value)
						$_POST["SHOW_COLUMN"][] = $key;
				}

				$arFieldsColumns = array();
				foreach ($_POST["SHOW_COLUMN"] as $col)
				{
					if (array_key_exists($col, $arResult["TaskFields"]) && $arResult["TaskFields"][$col]["SELECTABLE"])
						$arFieldsColumns[$col] = IntVal((is_array($_POST["ORDER_COLUMN"]) && array_key_exists($col, $_POST["ORDER_COLUMN"])) ? $_POST["ORDER_COLUMN"][$col] : 0);
				}

				$arFieldsFilter = array();
				if (array_key_exists("FILTER", $_POST) && is_array($_POST["FILTER"]) && Count($_POST["FILTER"]) > 0)
				{
					foreach ($_POST["FILTER"] as $key => $value)
					{
						if (array_key_exists($key, $arResult["TaskFields"]) && $arResult["TaskFields"][$key]["FILTERABLE"])
						{
							if ($key == "TASKSTATUS")
							{
								if ($_POST["TASK_PROP_STATUS"] == "active")
								{
									$arFieldsFilter[$key] = "active";
								}
								elseif ($_POST["TASK_PROP_STATUS"] == "selected")
								{
									$arFieldsFilter[$key] = $value;
								}
							}
							elseif ($arResult["TaskFields"][$key]["TYPE"] == "user")
							{
								if (array_key_exists("USER_TYPE_FILTER", $_POST) && is_array($_POST["USER_TYPE_FILTER"]))
								{
									if ($_POST["USER_TYPE_FILTER"][$key] == "current")
									{
										$arFieldsFilter[$key] = "current";
									}
									elseif ($_POST["USER_TYPE_FILTER"][$key] == "selected")
									{
										$arFoundUsers = CSocNetUser::SearchUser($value, true);
										if ($arFoundUsers && is_array($arFoundUsers) && count($arFoundUsers) > 0)
										{
											foreach ($arFoundUsers as $userID => $userName)
											{
												$arFieldsFilter[$key] = IntVal($userID);
												break;
											}
										}
									}
								}
							}
							elseif ($arResult["TaskFields"][$key]["TYPE"] == "datetime")
							{
								if (array_key_exists("DATE_TYPE_FILTER", $_POST) && is_array($_POST["DATE_TYPE_FILTER"]))
								{
									if ($_POST["DATE_TYPE_FILTER"][$key] == "current")
										$arFieldsFilter[$key] = "current";
									elseif ($_POST["DATE_TYPE_FILTER"][$key] == "selected")
										$arFieldsFilter[$key] = $value;
								}
							}
							elseif ($arResult["TaskFields"][$key]["TYPE"] == "bool")
							{
								if (StrLen($value) > 0)
									$arFieldsFilter[$key] = ($value == "Y" ? "Y" : "N");
							}
							else
							{
								if (StrLen($value) > 0)
									$arFieldsFilter[$key] = $value;
							}
						}
					}
				}

				$arFields = array(
					"TEMPLATE" => $userTemplateId,
					"TITLE" => (StrLen($_POST["TITLE"]) > 0 ? $_POST["TITLE"] : "No name"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => $arFieldsColumns,
					"ORDER_BY_0" => "",
					"ORDER_DIR_0" => "",
					"ORDER_BY_1" => "",
					"ORDER_DIR_1" => "",
					"FILTER" => $arFieldsFilter,
					"THROUGH_SAMPLING" => ((($_POST["THROUGH_SAMPLING"] == "Y") || ($taskType == "user")) ? "Y" : "N"),
				);

				if (strLen($_POST["ORDER_BY_0"]) > 0 && array_key_exists($_POST["ORDER_BY_0"], $arResult["TaskFields"]))
				{
					$arFields["ORDER_BY_0"] = $_POST["ORDER_BY_0"];
					$arFields["ORDER_DIR_0"] = (($_POST["ORDER_DIR_0"] == "ASC") ? "ASC" : "DESC");
				}
				if (strLen($_POST["ORDER_BY_1"]) > 0 && array_key_exists($_POST["ORDER_BY_1"], $arResult["TaskFields"]))
				{
					$arFields["ORDER_BY_1"] = $_POST["ORDER_BY_1"];
					$arFields["ORDER_DIR_1"] = (($_POST["ORDER_DIR_1"] == "ASC") ? "ASC" : "DESC");
				}

				$_POST["COMMON"] = (($arResult["Perms"]["CanModifyCommon"] && $_POST["COMMON"] == "Y") ? true : false);
				$arFields["COMMON"] = ($_POST["COMMON"] ? "Y" : "N");

				$arResult["UserSettings"] = $arFields;

				if ($action == "create")
				{
					$newID = 0;

					$dbUserOptionsList = CUserOptions::GetList(
						array("ID" => "DESC"),
						array()
					);
					if ($arUserOptionTmp = $dbUserOptionsList->Fetch())
						$newID = IntVal($arUserOptionTmp["ID"]);

					$newID++;
				}
				else
				{
					$newID = $viewId;
					CUserOptions::DeleteOption($userSettingsCategory, $userSettingsNamePart.$newID, true, $GLOBALS["USER"]->GetID());
					CUserOptions::DeleteOption($userSettingsCategory, $userSettingsNamePart.$newID, false, $GLOBALS["USER"]->GetID());
				}

				CUserOptions::SetOption($userSettingsCategory, $userSettingsNamePart.$newID, $arFields, $_POST["COMMON"], $GLOBALS["USER"]->GetID());

				$redirectPath = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array("owner_id" => $ownerId));
				if (StrPos($redirectPath, "?") === false)
					$redirectPath .= "?user_settings_id=".$newID;
				else
					$redirectPath .= "&user_settings_id=".$newID;
				LocalRedirect($redirectPath);
			}
		}
		else
		{
			$arResult["ShowStep"] = 1;

			$arResult["Templates"] = array();
			foreach ($arUserTemplatesList as $arUserTemplate)
			{
				$arUserTemplate["LINK"] = htmlspecialcharsbx($APPLICATION->GetCurPageParam("user_template_id=".$arUserTemplate["NAME"], array("user_template_id")));
				if (StrLen($arUserTemplate["TITLE"]) <= 0)
					$arUserTemplate["TITLE"] = $arUserTemplate["NAME"];
				$arResult["Templates"][] = $arUserTemplate;
			}

			$arResult["Settings"] = array();
			$dbUserOptionsList = CUserOptions::GetList(
				array("ID" => "ASC"),
				array("USER_ID_EXT" => $GLOBALS["USER"]->GetID(), "CATEGORY" => $userSettingsCategory)
			);
			while ($arUserOptionTmp = $dbUserOptionsList->Fetch())
			{
				$val = unserialize($arUserOptionTmp["VALUE"]);

				if ($val["IBLOCK_ID"] != $iblockId || $val["TASK_TYPE"] != $taskType || $val["OWNER_ID"] != $ownerId)
					continue;

				$id = IntVal(SubStr($arUserOptionTmp["NAME"], $userSettingsNamePartLength));
				$arResult["Settings"][] = array(
					"ID" => $id,
					"TITLE" => HtmlSpecialCharsbx($val["TITLE"]),
					"LINK" => htmlspecialcharsbx($APPLICATION->GetCurPageParam("user_template_id=".$val["TEMPLATE"]."&user_settings_id=".$id, array("user_template_id", "user_settings_id"))),
				);
			}
		}
	}

	$arResult["arSocNetFeaturesSettings"] = CSocNetAllowed::GetAllowedFeatures();
}

$this->IncludeComponentTemplate();
?>