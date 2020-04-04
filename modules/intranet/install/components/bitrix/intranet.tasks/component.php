<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("intranet"))
	return ShowError(GetMessage("EC_IBLOCK_MODULE_NOT_INSTALLED"));
if (!CModule::IncludeModule("iblock"))
	return ShowError(GetMessage("EC_IBLOCK_MODULE_NOT_INSTALLED"));
if (!CModule::IncludeModule("socialnetwork"))
	return ShowError(GetMessage("EC_SONET_MODULE_NOT_INSTALLED"));
if (!CModule::IncludeModule("bizproc"))
	return ShowError(GetMessage("EC_BIZPROC_MODULE_NOT_INSTALLED"));

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$GLOBALS["DBType"]."/favorites.php");

$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
$arParams["IBLOCK_ID"] = $iblockId;

$taskType = strtolower($arParams["TASK_TYPE"]);
if (!in_array($taskType, array("group", "user")))
	$taskType = "user";

global $USER, $APPLICATION;

$ownerId = intval($arParams["OWNER_ID"]);
if ($ownerId <= 0)
{
	$taskType = "user";
	$ownerId = $USER->GetID();
}
$ownerId = intval($ownerId);

$arParams["TASK_VAR"] = trim($arParams["TASK_VAR"]);
if (strlen($arParams["TASK_VAR"]) <= 0)
	$arParams["TASK_VAR"] = "task_id";

$arParams["GROUP_VAR"] = trim($arParams["GROUP_VAR"]);
if (strlen($arParams["GROUP_VAR"]) <= 0)
	$arParams["GROUP_VAR"] = "group_id";

$arParams["USER_VAR"] = trim($arParams["USER_VAR"]);
if (strlen($arParams["USER_VAR"]) <= 0)
	$arParams["USER_VAR"] = "user_id";

$arParams["VIEW_VAR"] = trim($arParams["VIEW_VAR"]);
if (strlen($arParams["VIEW_VAR"]) <= 0)
	$arParams["VIEW_VAR"] = "view_id";

$arParams["ACTION_VAR"] = trim($arParams["ACTION_VAR"]);
if (strlen($arParams["ACTION_VAR"]) <= 0)
	$arParams["ACTION_VAR"] = "action";

if (strlen($arParams["PAGE_VAR"]) <= 0)
	$arParams["PAGE_VAR"] = "page";

$arParams["PATH_TO_GROUP_TASKS"] = trim($arParams["PATH_TO_GROUP_TASKS"]);
if (strlen($arParams["PATH_TO_GROUP_TASKS"]) <= 0)
	$arParams["PATH_TO_GROUP_TASKS"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks&".$arParams["GROUP_VAR"]."=#group_id#");
$arParams["PATH_TO_USER_TASKS"] = trim($arParams["PATH_TO_USER_TASKS"]);
if (strlen($arParams["PATH_TO_USER_TASKS"]) <= 0)
	$arParams["PATH_TO_USER_TASKS"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks&".$arParams["USER_VAR"]."=#user_id#");

$arParams["PATH_TO_GROUP_TASKS_TASK"] = trim($arParams["PATH_TO_GROUP_TASKS_TASK"]);
if (strlen($arParams["PATH_TO_GROUP_TASKS_TASK"]) <= 0)
	$arParams["PATH_TO_GROUP_TASKS_TASK"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks_task&".$arParams["GROUP_VAR"]."=#group_id#&".$arParams["TASK_VAR"]."=#task_id#&".$arParams["ACTION_VAR"]."=#action#");
$arParams["PATH_TO_USER_TASKS_TASK"] = trim($arParams["PATH_TO_USER_TASKS_TASK"]);
if (strlen($arParams["PATH_TO_USER_TASKS_TASK"]) <= 0)
	$arParams["PATH_TO_USER_TASKS_TASK"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_task&".$arParams["USER_VAR"]."=#user_id#&".$arParams["TASK_VAR"]."=#task_id#&".$arParams["ACTION_VAR"]."=#action#");

$arParams["PATH_TO_GROUP_TASKS_VIEW"] = trim($arParams["PATH_TO_GROUP_TASKS_VIEW"]);
if (strlen($arParams["PATH_TO_GROUP_TASKS_VIEW"]) <= 0)
	$arParams["PATH_TO_GROUP_TASKS_VIEW"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks_view&".$arParams["GROUP_VAR"]."=#group_id#&".$arParams["VIEW_VAR"]."=#view_id#&".$arParams["ACTION_VAR"]."=#action#");
$arParams["PATH_TO_USER_TASKS_VIEW"] = trim($arParams["PATH_TO_USER_TASKS_VIEW"]);
if (strlen($arParams["PATH_TO_USER_TASKS_VIEW"]) <= 0)
	$arParams["PATH_TO_USER_TASKS_VIEW"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_view&".$arParams["USER_VAR"]."=#user_id#&".$arParams["VIEW_VAR"]."=#view_id#&".$arParams["ACTION_VAR"]."=#action#");

if ($taskType == "user")
{
	$arParams["PATH_TO_TASKS"] = str_replace("#user_id#", "#owner_id#", $arParams["PATH_TO_USER_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#user_id#", "#owner_id#", $arParams["PATH_TO_USER_TASKS_TASK"]);
	$arParams["PATH_TO_TASKS_VIEW"] = str_replace("#user_id#", "#owner_id#", $arParams["PATH_TO_USER_TASKS_VIEW"]);
}
else
{
	$arParams["PATH_TO_TASKS"] = str_replace("#group_id#", "#owner_id#", $arParams["PATH_TO_GROUP_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#group_id#", "#owner_id#", $arParams["PATH_TO_GROUP_TASKS_TASK"]);
	$arParams["PATH_TO_TASKS_VIEW"] = str_replace("#group_id#", "#owner_id#", $arParams["PATH_TO_GROUP_TASKS_VIEW"]);
}

$arParams["ITEMS_COUNT"] = intval($arParams["ITEMS_COUNT"]);
if ($arParams["ITEMS_COUNT"] <= 0)
	$arParams["ITEMS_COUNT"] = 20;

$arParams['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'] ? $arParams['NAME_TEMPLATE'] : CSite::GetNameFormat();
$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;

if (!array_key_exists("PATH_TO_MESSAGES_CHAT", $arParams))
	$arParams["PATH_TO_MESSAGES_CHAT"] = "/company/personal/messages/chat/#user_id#/";
if (!array_key_exists("PATH_TO_USER", $arParams))
	$arParams["PATH_TO_USER"] = "/company/personal/user/#user_id#/";
if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams))
	$arParams["PATH_TO_CONPANY_DEPARTMENT"] = $arParams["~PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";

// for bitrix:main.user.link
$arTooltipFieldsDefault	= serialize(array(
	"EMAIL",
	"PERSONAL_MOBILE",
	"WORK_PHONE",
	"PERSONAL_ICQ",
	"PERSONAL_PHOTO",
	"PERSONAL_CITY",
	"WORK_COMPANY",
	"WORK_POSITION",
));
$arTooltipPropertiesDefault = serialize(array(
	"UF_DEPARTMENT",
	"UF_PHONE_INNER",
));

if (!array_key_exists("SHOW_FIELDS_TOOLTIP", $arParams))
	$arParams["SHOW_FIELDS_TOOLTIP"] = unserialize(COption::GetOptionString("socialnetwork", "tooltip_fields", $arTooltipFieldsDefault));
if (!array_key_exists("USER_PROPERTY_TOOLTIP", $arParams))
	$arParams["USER_PROPERTY_TOOLTIP"] = unserialize(COption::GetOptionString("socialnetwork", "tooltip_properties", $arTooltipPropertiesDefault));

$userSettingsCategory = "IntranetTasks";
$userSettingsNamePart = "Settings_";
$userSettingsNamePartLength = strlen($userSettingsNamePart);

if (!$USER->IsAuthorized())
{	
	$arResult["NEED_AUTH"] = "Y";
}
else
{
	$arResult["FatalError"] = "";

	$arParams["TASK_TYPE"] = $taskType;
	$arParams["OWNER_ID"] = $ownerId;

	if (strlen($arResult["FatalError"]) <= 0)
	{
		if (!CIntranetTasks::IsTasksFeatureActive($taskType, $ownerId))
			$arResult["FatalError"] .= GetMessage("INTS_TASKS_OFF").".";
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		if (!CIntranetTasks::CanCurrentUserPerformOperation($taskType, $ownerId, "view"))
			$arResult["FatalError"] .= GetMessage("INTS_NO_SONET_PERMS").".";
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		$globalParentSectionId = CIntranetTasks::InitializeIBlock($taskType, $ownerId, $arParams["FORUM_ID"]);
		if ($globalParentSectionId <= 0)
			$arResult["FatalError"] .= GetMessage("INTS_NO_TASK").". ";
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		$arResult["TaskFields"] = CIntranetTasks::GetTaskFields($taskType, $ownerId);
		$arResult["TaskFields"]["TASKSTATUS"] = array(
			"NAME" => GetMessage("INTASK_L_TASKSTATUS"),
			"FULL_NAME" => GetMessage("INTASK_L_TASKSTATUS"),
			"FILTERABLE" => true,
		);
		$arResult["TaskFieldsMap"] = CIntranetTasks::GetTaskFieldsMap($arResult["TaskFields"]);
		$arResult["TaskFieldsMap"]["TASKSTATUS"] = "TASKSTATUS";


		$arTaskStatusOldLink = array();
		$arTaskStatusOldLinkMap = array("NOTACCEPTED" => "NotAccepted", "NOTSTARTED" => "NotStarted", "INPROGRESS" => "InProgress", "COMPLETED" => "Closed", "WAITING" => "Waiting", "DEFERRED" => "Deferred");
		$dbRes = CIBlockProperty::GetPropertyEnum("TaskStatus", Array("SORT" => "ASC"), Array("IBLOCK_ID" => $iblockId));
		while ($arRes = $dbRes->Fetch())
			$arTaskStatusOldLink[$arRes["ID"]] = $arTaskStatusOldLinkMap[strtoupper($arRes["XML_ID"])];
	}

	/**************************  ACTIONS  **********************************/
	if (strlen($arResult["FatalError"]) <= 0)
	{
		if (isset($_GET['bx_task_action_request']) && $_GET['bx_task_action_request'] == 'Y')
		{
			define("BX_INTASKS_FROM_COMPONENT", true);
			$GLOBALS["APPLICATION"]->RestartBuffer();

			include($_SERVER['DOCUMENT_ROOT']."/bitrix/components/bitrix/intranet.tasks/action_ajax.php");
			die();
		}

		if (isset($_GET['action']) && strlen($_GET['action']) > 0 && check_bitrix_sessid())
		{
			if ($_GET['action'] == 'delete_task')
			{
				$delTaskId = IntVal($_GET['del_task_id']);
				if ($delTaskId > 0)
				{
					$currentUserCanDeleteTask = CIntranetTasksDocument::CanUserOperateDocument(
						INTASK_DOCUMENT_OPERATION_DELETE_DOCUMENT,
						$GLOBALS["USER"]->GetID(),
						$delTaskId,
						array()
					);
					if ($currentUserCanDeleteTask)
					{
						$arErrorsTmp = array();
						CIntranetTasks::Delete($delTaskId, $arErrorsTmp);
						if (count($arErrorsTmp) > 0)
						{
							foreach ($arErrorsTmp as $e)
								$arResult["ErrorMessage"] .= $e["message"]."<br />";
						}
					}
					else
					{
						$arResult["ErrorMessage"] .= GetMessage("INTS_NO_TASK_DELETE_PERMS").".";
					}
				}
			}
			elseif ($_GET['action'] == 'delete_view')
			{
				$delViewId = intval($_GET['del_view_id']);
				if ($delViewId > 0)
					$arResult["ErrorMessage"] .= CIntranetTasks::__InTaskDeleteView($delViewId, $iblockId, $taskType, $ownerId);
			}
			else
			{
				$actionTaskId = intval($_GET['action_task_id']);
				$wf = trim($_GET['wf']);
				if ($actionTaskId > 0 && strlen($wf) > 0)
				{
					$arErrorsTmp = array();

					$arTaskTmp = CIntranetTasks::GetById($actionTaskId);
					if (!$arTaskTmp)
						$arErrorsTmp[] = GetMessage("INTS_NO_TASK").".<br />";

					if (count($arErrorsTmp) <= 0)
					{
						$arCurrentUserGroups = array();

						if ($arTaskTmp["TaskType"] == "group")
						{
							$arCurrentUserGroups[] = SONET_ROLES_ALL;

							if ($GLOBALS["USER"]->IsAuthorized())
								$arCurrentUserGroups[] = SONET_ROLES_AUTHORIZED;

							$r = CSocNetUserToGroup::GetUserRole($USER->GetID(), $arTaskTmp["OwnerId"]);
							if (strlen($r) > 0)
								$arCurrentUserGroups[] = $r;
						}
						else
						{
							$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_ALL;

							if ($GLOBALS["USER"]->IsAuthorized())
								$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_AUTHORIZED;

							if (CSocNetUserRelations::IsFriends($USER->GetID(), $arTaskTmp["ownerId"]))
								$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_FRIENDS;
							elseif (CSocNetUserRelations::IsFriends2($USER->GetID(), $arTaskTmp["ownerId"]))
								$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_FRIENDS2;
						}

						if ($USER->GetID() == $arTaskTmp["CREATED_BY"])
							$arCurrentUserGroups[] = "author";
						if ($USER->GetID() == $arTaskTmp["PROPERTY_TaskAssignedTo"])
							$arCurrentUserGroups[] = "responsible";
						if (is_array($arTaskTmp["PROPERTY_TaskTrackers"]) && in_array($USER->GetID(), $arTaskTmp["PROPERTY_TaskTrackers"]))
							$arCurrentUserGroups[] = "trackers";
					}

					if (count($arErrorsTmp) <= 0)
					{
						$iblockElementObject = new CIBlockElement();
						$iblockElementObject->Update(intval($actionTaskId), array("MODIFIED_BY" => $GLOBALS["USER"]->GetID()));

						CBPDocument::SendExternalEvent(
							$wf,
							$_GET['action'],
							array("Groups" => $arCurrentUserGroups, "User" => $GLOBALS["USER"]->GetID()),
							$arErrorTmp
						);
					}

					if (count($arErrorsTmp) > 0)
					{
						foreach ($arErrorsTmp as $e)
							$arResult["ErrorMessage"] .= $e["message"]."<br />";
					}
				}
			}

			if (strlen($arResult["ErrorMessage"]) <= 0)
			{
				if (array_key_exists("back_url", $_REQUEST) && strlen($_REQUEST["back_url"]) > 0)
					$redirectPath = $_REQUEST["back_url"];
				else
					$redirectPath = $APPLICATION->GetCurPageParam("", array("action", "del_task_id", "del_view_id", 'action_task_id', 'wf'));

				LocalRedirect($redirectPath);
			}
		}
	}
	/**************************  END ACTIONS  **********************************/

	/**************************  SETTINGS  **********************************/
	if (strlen($arResult["FatalError"]) <= 0)
	{
		if ($taskType == "user")
		{
			$dbUser = CUser::GetByID($ownerId);
			$arResult["Owner"] = $dbUser->GetNext();
		}
		else
		{
			$arResult["Owner"] = CSocNetGroup::GetByID($ownerId);
		}

		if ($taskType == "user" && ($arParams["SET_TITLE"] == "Y" || $arParams["SET_NAVCHAIN"] != "N"))	
		{
			if (strlen($arParams["NAME_TEMPLATE"]) <= 0)		
				$arParams["NAME_TEMPLATE"] = '#NOBR##NAME# #LAST_NAME##/NOBR#';
				
			$arParams["TITLE_NAME_TEMPLATE"] = str_replace(
				array("#NOBR#", "#/NOBR#"), 
				array("", ""), 
				$arParams["NAME_TEMPLATE"]
			);

			$bUseLogin = $arParams['HIDE_LOGIN'] == "Y" ? false : true;		

			$arTmpUser = array(
						'NAME' => $arResult["Owner"]["~NAME"],
						'LAST_NAME' => $arResult["Owner"]["~LAST_NAME"],
						'SECOND_NAME' => $arResult["Owner"]["~SECOND_NAME"],
						'LOGIN' => $arResult["Owner"]["~LOGIN"],
					);
			$strTitleFormatted = CUser::FormatName($arParams['TITLE_NAME_TEMPLATE'], $arTmpUser, $bUseLogin);
		}

		if ($arParams["SET_TITLE"] == "Y" || $arParams["SET_NAVCHAIN"] != "N")
		{
			$feature = "tasks";
			$arEntityActiveFeatures = CSocNetFeatures::GetActiveFeaturesNames((($taskType == 'user') ? SONET_ENTITY_USER : SONET_ENTITY_GROUP), $arResult["Owner"]["ID"]);		
			$strFeatureTitle = ((array_key_exists($feature, $arEntityActiveFeatures) && StrLen($arEntityActiveFeatures[$feature]) > 0) ? $arEntityActiveFeatures[$feature] : GetMessage("INTASK_C36_PAGE_TITLE"));
		}
		
		if ($arParams["SET_TITLE"] == "Y")
		{
			if ($taskType == "user")
				$APPLICATION->SetTitle($strTitleFormatted.": ".$strFeatureTitle);
			else
				$APPLICATION->SetTitle($arResult["Owner"]["NAME"].": ".$strFeatureTitle);
		}

		if ($arParams["SET_NAVCHAIN"] != "N")
		{
			if ($taskType == "user")
			{
				$APPLICATION->AddChainItem($strTitleFormatted, CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_USER"], array("user_id" => $arParams["OWNER_ID"])));
				$APPLICATION->AddChainItem($strFeatureTitle);
			}
			else
			{
				$APPLICATION->AddChainItem($arResult["Owner"]["NAME"], CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_GROUP"], array("group_id" => $arParams["OWNER_ID"])));
				$APPLICATION->AddChainItem($strFeatureTitle);
			}
		}

		$userSettingsId = intval($arParams["USER_SETTINGS_ID"]);
		if (strlen($arParams["USER_SETTINGS_ID"]) <= 0)
		{
			$userSettingsId = intval($_REQUEST["user_settings_id"]);
			if (strlen($_REQUEST["user_settings_id"]) <= 0 
				&& array_key_exists("INTASK_TASKVIEW_current_view", $_SESSION)
				&& is_array($_SESSION["INTASK_TASKVIEW_current_view"]) 
				&& array_key_exists($taskType."-".$ownerId, $_SESSION["INTASK_TASKVIEW_current_view"]))
			{
				$userSettingsId = intval($_SESSION["INTASK_TASKVIEW_current_view"][$taskType."-".$ownerId]);
			}

			if (!array_key_exists("INTASK_TASKVIEW_current_view", $_SESSION) || !is_array($_SESSION["INTASK_TASKVIEW_current_view"]))
				$_SESSION["INTASK_TASKVIEW_current_view"] = array();
			$_SESSION["INTASK_TASKVIEW_current_view"][$taskType."-".$ownerId] = $userSettingsId;
		}

		$arUserSettings = false;
		if ($userSettingsId > 0)
		{
			$arUserSettings = CUserOptions::GetOption($userSettingsCategory, $userSettingsNamePart.$userSettingsId, false, $GLOBALS["USER"]->GetID());
			if ($arUserSettings && is_array($arUserSettings) && count($arUserSettings) > 0)
			{
				if ($arUserSettings["IBLOCK_ID"] != $iblockId || $arUserSettings["TASK_TYPE"] != $taskType || $arUserSettings["OWNER_ID"] != $ownerId)
				{
					$arUserSettings = false;
					$userSettingsId = 0;
				}
			}
			else
			{
				$arUserSettings = false;
				$userSettingsId = 0;
			}
		}

		$arResult["useTemplateId"] = "";
		if ($arUserSettings)
		{
			$arResult["useTemplateId"] = $arUserSettings["TEMPLATE"];

			for ($i = 0; $i < 3; $i++)
			{
				if (array_key_exists("ORDER_BY_".$i, $arUserSettings))
				{
					$arUserSettings["ORDER_BY_".$i] = strtoupper(trim($arUserSettings["ORDER_BY_".$i]));
					if (array_key_exists($arUserSettings["ORDER_BY_".$i], $arResult["TaskFieldsMap"]))
					{
						$arParams["ORDER_BY_".$i] = $arUserSettings["ORDER_BY_".$i];
						$arParams["ORDER_DIR_".$i] = strtoupper(trim($arUserSettings["ORDER_DIR_".$i]));
						if (!in_array($arParams["ORDER_DIR_".$i], array("ASC", "DESC")))
							$arParams["ORDER_DIR_".$i] = "ASC";
					}
					else
					{
						$arParams["ORDER_BY_".$i] = "";
						$arParams["ORDER_DIR_".$i] = "";
					}
				}
				else
				{
					$arParams["ORDER_BY_".$i] = "";
					$arParams["ORDER_DIR_".$i] = "";
				}
			}

			$arParams["THROUGH_SAMPLING"] = strtoupper($arUserSettings["THROUGH_SAMPLING"]);

			$arParams["FILTER"] = array();
			if (is_array($arUserSettings["FILTER"]))
			{
				foreach ($arUserSettings["FILTER"] as $key => $value)
				{
					$key = strtoupper(trim($key));

					$op = "";
					$opTmp = substr($key, 0, 1);
					if (in_array($opTmp, array("!", "<", ">")))
					{
						$op = $opTmp;
						$key = substr($key, 1);
					}

					if (array_key_exists($key, $arResult["TaskFieldsMap"]) && $arResult["TaskFields"][$arResult["TaskFieldsMap"][$key]]["FILTERABLE"])
					{
						$arF = $arResult["TaskFields"][$arResult["TaskFieldsMap"][$key]];
						if ($key == "TASKSTATUS")
						{
							if ($value == "active")
								$arParams["FILTER"]["!".$key] = "Closed";
							else
								$arParams["FILTER"][$op.$key] = (array_key_exists($value, $arTaskStatusOldLink) ? $arTaskStatusOldLink[$value] : $value);
						}
						elseif ($arF["Type"] == "datetime")
						{
							if ($value == "current")
								$arParams["FILTER"][$op.$key] = date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE));
							else
								$arParams["FILTER"][$op.$key] = $value;
						}
						elseif ($arF["Type"] == "user")
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

			$arParams["COLUMNS"] = array();
			if (is_array($arUserSettings["COLUMNS"]))
			{
				$ar = array();
				foreach ($arUserSettings["COLUMNS"] as $key => $value)
				{
					$key = strtoupper(trim($key));
					if (array_key_exists($key, $arResult["TaskFieldsMap"]))
						$ar[$key] = $value;
				}
				$arKeys = array_keys($ar);
				$arVals = array_values($ar);
				for ($i = 0; $i < Count($arVals) - 1; $i++)
				{
					for ($j = $i + 1; $j < count($arVals); $j++)
					{
						if ($arVals[$i] > $arVals[$j])
						{
							$t = $arVals[$i];
							$arVals[$i] = $arVals[$j];
							$arVals[$j] = $t;

							$t = $arKeys[$i];
							$arKeys[$i] = $arKeys[$j];
							$arKeys[$j] = $t;
						}
					}
				}
				foreach ($arKeys as $key)
					$arParams["COLUMNS"][] = $key;
			}
		}
		else
		{
			$arResult["useTemplateId"] = (array_key_exists("template", $_REQUEST) ? $_REQUEST["template"] : $arParams["TEMPLATE"]);
		}

		for ($i = 0; $i < 3; $i++)
		{
			$orderBy = (array_key_exists("order_by_".$i, $_REQUEST) ? $_REQUEST["order_by_".$i] : $arParams["ORDER_BY_".$i]);
			$orderDir = (array_key_exists("order_dir_".$i, $_REQUEST) ? $_REQUEST["order_dir_".$i] : $arParams["ORDER_DIR_".$i]);

			$orderBy = strtoupper(trim($orderBy));
			if (array_key_exists($orderBy, $arResult["TaskFieldsMap"]))
			{
				$arParams["ORDER_BY_".$i] = $orderBy;
				$arParams["ORDER_DIR_".$i] = strtoupper(trim($orderDir));
				if (!in_array($arParams["ORDER_DIR_".$i], array("ASC", "DESC")))
					$arParams["ORDER_DIR_".$i] = "ASC";
			}
			else
			{
				$arParams["ORDER_BY_".$i] = "";
				$arParams["ORDER_DIR_".$i] = "";
			}
		}

		$arParams["THROUGH_SAMPLING"] = strtoupper(array_key_exists("through_sampling", $_REQUEST) ? $_REQUEST["through_sampling"] : $arParams["THROUGH_SAMPLING"]);
		if ($arParams["THROUGH_SAMPLING"] != "Y" && $arParams["THROUGH_SAMPLING"] != "N")
			$arParams["THROUGH_SAMPLING"] = "N";
		if ($taskType != 'group')
			$arParams["THROUGH_SAMPLING"] = "Y";

		foreach ($arParams as $key => $value)
		{
			if (strtolower(substr($key, 0, 4)) != "FLT_")
				continue;
			if (!is_array($value) && strlen($value) <= 0 || is_array($value) && count($value) <= 0)
				continue;

			$key = strtoupper(substr($key, 4));

			$op = "";
			$opTmp = substr($key, 0, 1);
			if (in_array($opTmp, array("!", "<", ">")))
			{
				$op = $opTmp;
				$key = substr($key, 1);
			}

			if (array_key_exists($key, $arResult["TaskFieldsMap"]) && $arResult["TaskFields"][$arResult["TaskFieldsMap"][$key]]["FILTERABLE"])
			{
				$arF = $arResult["TaskFields"][$arResult["TaskFieldsMap"][$key]];
				if ($arF["Type"] == "datetime")
				{
					if ($value == "current")
						$arParams["FILTER"][$op.$key] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE));
					else
						$arParams["FILTER"][$op.$key] = $value;
				}
				elseif ($arF["Type"] == "user")
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
			if (strtolower(substr($key, 0, 4)) != "flt_")
				continue;
			if (!is_array($value) && strlen($value) <= 0 || is_array($value) && count($value) <= 0)
				continue;

			$key = strtoupper(substr($key, 4));

			$op = "";
			$opTmp = substr($key, 0, 1);
			if (in_array($opTmp, array("!", "<", ">")))
			{
				$op = $opTmp;
				$key = substr($key, 1);
			}

			if (array_key_exists($key, $arResult["TaskFieldsMap"]) && $arResult["TaskFields"][$arResult["TaskFieldsMap"][$key]]["FILTERABLE"])
			{
				$arF = $arResult["TaskFields"][$arResult["TaskFieldsMap"][$key]];
				if ($arF["Type"] == "datetime")
				{
					if ($value == "current")
						$arParams["FILTER"][$op.$key] = date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE));
					else
						$arParams["FILTER"][$op.$key] = $value;
				}
				elseif ($arF["Type"] == "user")
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

		if (count($arParams["COLUMNS"]) <= 0)
		{
			$arParams["COLUMNS"] = array(
				"NAME", "TIMESTAMP_X", "TASKASSIGNEDTO", "TASKPRIORITY", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO", "TASKSTATUS", "TASKCOMPLETE"
			);
		}

		if (!$arUserSettings && count($arParams["FILTER"]) <= 0)
			$arParams["FILTER"]["!TASKSTATUS"] = "Closed";
	}
	/**************************  END SETTINGS  **********************************/
	//echo "<pre>".print_r($arParams["FILTER"], true)."</pre>";

	/**************************  FILTER  **********************************/
	if (strlen($arResult["FatalError"]) <= 0)
	{
		$arResult["Error"] = "";

		$arResult["isCurrentUser"] = false;
		if ($taskType == "user")
			$arResult["isCurrentUser"] = ($ownerId == $GLOBALS["USER"]->GetID());

		$arOrderBy = array();
		for ($i = 0; $i < 3; $i++)
		{
			if (strlen($arParams["ORDER_BY_".$i]) <= 0)
				continue;

			if (array_key_exists($arParams["ORDER_BY_".$i], $arResult["TaskFieldsMap"]))
			{
				$arParams["ORDER_DIR_".$i] = (strtoupper($arParams["ORDER_DIR_".$i]) == "ASC" ? "ASC" : "DESC");

				$arOrderBy[$arResult["TaskFieldsMap"][$arParams["ORDER_BY_".$i]]] = $arParams["ORDER_DIR_".$i];
				if ($arParams["ORDER_BY_".$i] == "DATE_ACTIVE_TO")
					$arOrderBy[$arResult["TaskFieldsMap"][$arParams["ORDER_BY_".$i]]] .= ",nulls";
			}
		}

		if (count($arOrderBy) <= 0)
		{
			$arOrderBy["TIMESTAMP_X"] = "DESC";
			$arOrderBy["ID"] = "DESC";
		}


		$arFilter = array("IBLOCK_ID" => $iblockId, "ACTIVE" => "Y");
		if ($arParams["THROUGH_SAMPLING"] == "N")
			$arSectionFilter = array("IBLOCK_ID" => $iblockId, "ACTIVE" => "Y");

		if ($taskType != "user")
			$arFilter["SECTION_ID"] = $globalParentSectionId;

		if ($arParams["THROUGH_SAMPLING"] == "Y")
			$arFilter["INCLUDE_SUBSECTIONS"] = "Y";
		else
			$arSectionFilter["SECTION_ID"] = $globalParentSectionId;
	}
	/**************************  END FILTER  **********************************/

	if (strlen($arResult["FatalError"]) <= 0)
	{
		$arSectionsChain = array();
		if (is_array($arParams["FILTER"]))
		{
			foreach ($arParams["FILTER"] as $key => $value)
			{
				$op = "";
				$opTmp = substr($key, 0, 1);
				if (in_array($opTmp, array("!", "<", ">")))
				{
					$op = $opTmp;
					$key = substr($key, 1);
				}

				if (array_key_exists($key, $arResult["TaskFieldsMap"]) && $arResult["TaskFields"][$arResult["TaskFieldsMap"][$key]]["FILTERABLE"])
				{
					$arF = $arResult["TaskFields"][$arResult["TaskFieldsMap"][$key]];
					if ($arResult["TaskFieldsMap"][$key] == "IBLOCK_SECTION_ID")
					{
						$bFirst = true;
						$dbSectionsChain = CIBlockSection::GetNavChain($iblockId, $value);
						while ($arSect = $dbSectionsChain->GetNext())
						{
							if ($bFirst)
							{
								if ($globalParentSectionId > 0 && $arSect["ID"] != $globalParentSectionId)
									break;
								$bFirst = false;
								continue;
							}
							$arSectionsChain[] = $arSect;
						}
						if (!$bFirst)
						{
							$arFilter["SECTION_ID"] = $value;
							if ($arParams["THROUGH_SAMPLING"] == "N")
								$arSectionFilter["SECTION_ID"] = $value;
						}
					}
					else
					{
						$arFilter[$op.$arResult["TaskFieldsMap"][$key]] = $value;
					}
				}
			}
		}

		$arResult["ParentSectionUrl"] = "";
		if ($arParams["THROUGH_SAMPLING"] == "N" && Count($arSectionsChain) > 0)
		{
			if (Count($arSectionsChain) > 1)
				$arResult["ParentSectionUrl"] = $APPLICATION->GetCurPageParam("flt_iblock_section=".$arSectionsChain[Count($arSectionsChain) - 2]["ID"], array("flt_iblock_section"));
			else
				$arResult["ParentSectionUrl"] = $APPLICATION->GetCurPageParam("", array("flt_iblock_section"));
		}

		if ($arParams["THROUGH_SAMPLING"] == "N")
		{
			//echo "<pre><b>CIBlockSection::GetList</b>\n".print_r(array(array("NAME" => "ASC"), $arSectionFilter), true)."</pre>";

			$bCanModifyFolders = CSocNetFeaturesPerms::CanPerformOperation(
				$GLOBALS["USER"]->GetID(),
				(($taskType == 'user') ? SONET_ENTITY_USER : SONET_ENTITY_GROUP),
				$ownerId,
				"tasks",
				"modify_folders"
			);

			$dbSectionsList = CIBlockSection::GetList(array("NAME" => "ASC"), $arSectionFilter);
			while ($arSection = $dbSectionsList->GetNext())
			{
				$arSection["ShowUrl"] = $APPLICATION->GetCurPageParam("flt_iblock_section=".$arSection["ID"], array("flt_iblock_section"));

				$arActions = array();

				$arActions[] = array(
					"ICON" => "",
					"TITLE" => GetMessage("INTS_ACTF_VIEW"),
					"CONTENT" => GetMessage("INTS_ACTF_VIEW_DESCR"),
					"ONCLICK" => "setTimeout(HideThisMenuS".$arSection["ID"].", 900); jsUtils.Redirect([], '".CUtil::JSEscape($arSection["ShowUrl"])."');",
				);

				if ($bCanModifyFolders)
				{
					$arActions[] = array(
						"ICON" => "",
						"TITLE" => GetMessage("INTS_ACTF_EDIT"),
						"CONTENT" => GetMessage("INTS_ACTF_EDIT_DESCR"),
						"ONCLICK" => "setTimeout(HideThisMenuS".$arSection["ID"].", 900); window.ITSIntTaskDialog.ShowFolderDlg(".CUtil::PhpToJSObject(array("ID" => $arSection["ID"], "NAME" => $arSection["NAME"])).");",
					);
					$arActions[] = array(
						"ICON" => "",
						"TITLE" => GetMessage("INTS_ACTF_DELETE"),
						"CONTENT" => GetMessage("INTS_ACTF_DELETE_DESCR"),
						"ONCLICK" => "setTimeout(HideThisMenuS".$arSection["ID"].", 900); window.ITSIntTaskDialog.DeleteFolder(".CUtil::PhpToJSObject(array("ID" => $arSection["ID"])).");",
					);
				}

				$arResult["Sections"][] = array(
					"FIELDS" => $arSection,
					"ACTIONS" => $arActions,
				);
			}
		}

		$strCurrentUrlTmp = $GLOBALS["APPLICATION"]->GetCurPageParam("", array("back_url"));
		$strUrlAppendix = "back_url=".urlencode($strCurrentUrlTmp);
		if (array_key_exists("flt_iblock_section", $_REQUEST))
		{
			$fsId = intval($_REQUEST["flt_iblock_section"]);
			if ($fsId > 0)
				$strUrlAppendix .= "&flt_iblock_section=".$fsId;
		}

		$arNavStartParams = array("nPageSize" => $arParams["ITEMS_COUNT"], "bShowAll" => false, "bDescPageNumbering" => false);
		$arNavigation = CDBResult::GetNavParams($arNavStartParams);

		$arSelectFields = array("ID", "NAME", "IBLOCK_ID", "CREATED_BY", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO", "DETAIL_TEXT");
		foreach ($arParams["COLUMNS"] as $field)
		{
			if (!in_array($field, $arSelectFields) && array_key_exists($field, $arResult["TaskFieldsMap"]))
				$arSelectFields[] = $field;
		}

		$arFilter["CHECK_BP_TASKS_PERMISSIONS"] = $taskType."_".$ownerId."_read";

		$arResult["TasksPropsShow"] = array();
		foreach ($arParams["COLUMNS"] as $field)
		{
			if (array_key_exists($field, $arResult["TaskFieldsMap"]))
				$arResult["TasksPropsShow"][] = $arResult["TaskFieldsMap"][$field];
		}

//$hFileTmp = fopen($_SERVER["DOCUMENT_ROOT"]."/+++++++.+++", "a");  // DUMPING
//fwrite($hFileTmp, "CIBlockElement::GetList:\n".print_r(array($arOrderBy, $arFilter, false, $arNavStartParams, $arSelectFields), true)."\n\n\n");
//fwrite($hFileTmp, "arResult:\n".print_r($arResult, true)."\n\n\n");
//fclose($hFileTmp);

		$arPermsCache = array();
		$dbTasksList = false;
		$arTasksListArray = array();

		if ($taskType == "user" && !$arResult["isCurrentUser"])
			$arFilter["PROPERTY_TASKASSIGNEDTO"] = $ownerId;

		list($dbTasksList, $dbTasksList1) = CIntranetTasks::GetListEx(
			$arOrderBy,
			$arFilter,
			false,
			$arNavStartParams,
			$arSelectFields,
			$arParams['NAME_TEMPLATE'],
			$bUseLogin,
			true,
			array(
				"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
				"THUMBNAIL_LIST_SIZE" => $arParams["THUMBNAIL_LIST_SIZE"],
				"USE_THUMBNAIL_LIST" => $arParams["USE_THUMBNAIL_LIST"],				
				"SHOW_YEAR" => $arParams["SHOW_YEAR"],
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
				"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["PATH_TO_MESSAGES_CHAT"],
				"PATH_TO_SONET_USER_PROFILE" => $arParams["PATH_TO_USER"],
				"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
				"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
				"INLINE" => $arParams["INLINE"],
			)
		);
		//$dbTasksList->NavStart($arParams["ITEMS_COUNT"]);
		while ($arTask = $dbTasksList->Fetch())
		{
			$currentTaskAssignedToUser = $arTask["PROPERTY_TaskAssignedTo"];

			$currentTaskStatusId = $arTask["DocumentState"]["STATE_NAME"];
			$currentTaskStatus = $arTask["DocumentState"]["STATE_TITLE"];

			if (is_array($arTask["PROPERTY_TaskPriority"]))
			{
				foreach ($arTask["PROPERTY_TaskPriority"] as $k => $v)
				{
					$currentTaskPriorityId = $k;
					$iii1 = 0;
					foreach ($arResult["TaskFields"]["PROPERTY_TaskPriority"]["Options"] as $kkk1 => $vvv1)
					{
						$iii1++;
						if ($vvv1 == $v)
						{
							$currentTaskPriority =  $iii1;
							break;
						}
					}
					break;
				}
			}

			$arActions = array();

			if ($arTask["TaskType"] == "group")
				$p1 = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_TASKS_TASK"], array("group_id" => $arTask["OwnerId"], "task_id" => $arTask["ID"], "action" => "view"));
			else
				$p1 = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_TASKS_TASK"], array("user_id" => $arTask["OwnerId"], "task_id" => $arTask["ID"], "action" => "view"));

			$arActions[] = array(
				"ICON" => "",
				"TITLE" => GetMessage("INTS_ACTT_VIEW"),
				"CONTENT" => "<b>".GetMessage("INTS_ACTT_VIEW_DESCR")."</b>",
				"ONCLICK" => "setTimeout(HideThisMenu".$arTask["ID"].", 900); jsUtils.Redirect([], '".CUtil::JSEscape($p1.(StrPos($p1, "?") === false ? "?" : "&").$strUrlAppendix)."');",
			);

			if (count($arTask["DocumentState"]["AllowableEvents"]) > 0)
			{
				if ($arTask["TaskType"] == "group")
					$p1 = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_TASKS"], array("group_id" => $arTask["OwnerId"]));
				else
					$p1 = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_TASKS"], array("user_id" => $arTask["OwnerId"]));

				foreach ($arTask["DocumentState"]["AllowableEvents"] as $e)
				{
					if (substr($e["NAME"], -strlen("_SetResponsibleEvent")) == "_SetResponsibleEvent")
						continue;

					$p11 = $p1.((strpos($p1, "?") !== false) ? "&" : "?");
					$p11 .= "action_task_id=".$arTask["ID"]."&".bitrix_sessid_get()."&action=".$e["NAME"]."&wf=".$arTask["DocumentState"]["ID"];
					$p11 .= "&back_url=".UrlEncode($GLOBALS["APPLICATION"]->GetCurPageParam("", array()));

					$arActions[] = array(
						"ICON" => "",
						"TITLE" => $e["TITLE"],
						"CONTENT" => $e["TITLE"],
						"ONCLICK" => "setTimeout(HideThisMenu".$arTask["ID"].", 900);jsUtils.Redirect([], '".CUtil::JSEscape($p11)."');",
					);
				}
			}

			if ($arTask["CurrentUserCanWriteTask"])
			{
				if ($arTask["TaskType"] == "group")
					$p1 = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_TASKS_TASK"], array("group_id" => $arTask["OwnerId"], "task_id" => $arTask["ID"], "action" => "edit"));
				else
					$p1 = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_TASKS_TASK"], array("user_id" => $arTask["OwnerId"], "task_id" => $arTask["ID"], "action" => "edit"));

				$arActions[] = array(
					"ICON" => "",
					"TITLE" => GetMessage("INTS_ACTT_EDIT"),
					"CONTENT" => GetMessage("INTS_ACTT_EDIT_DESCR"),
					"ONCLICK" => "setTimeout(HideThisMenu".$arTask["ID"].", 900); jsUtils.Redirect([], '".CUtil::JSEscape($p1.(StrPos($p1, "?") === false ? "?" : "&").$strUrlAppendix)."');",
				);
			}

			if ($arTask["CurrentUserCanDeleteTask"])
			{
				if ($arTask["TaskType"] == "group")
					$p1 = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_TASKS"], array("group_id" => $arTask["OwnerId"], "task_id" => $arTask["ID"], "action" => "edit"));
				else
					$p1 = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_TASKS"], array("user_id" => $arTask["OwnerId"], "task_id" => $arTask["ID"], "action" => "edit"));

				$p1 .= ((StrPos($p1, "?") !== false) ? "&" : "?");
				$p1 .= "del_task_id=".$arTask["ID"]."&".bitrix_sessid_get()."&action=delete_task";
				$p1 .= "&back_url=".UrlEncode($GLOBALS["APPLICATION"]->GetCurPageParam("", array()));

				$arActions[] = array(
					"ICON" => "",
					"TITLE" => GetMessage("INTS_ACTT_DELETE"),
					"CONTENT" => GetMessage("INTS_ACTT_DELETE_DESCR"),
					"ONCLICK" => "if(confirm('".CUtil::JSEscape(GetMessage("INTS_ACTT_DELETE_PROMT"))."')){jsUtils.Redirect([], '".CUtil::JSEscape($p1)."')};",
				);
			}

			if ($arTask["TaskType"] == "group")
				$p1 = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_TASKS_TASK"], array("group_id" => $arTask["OwnerId"], "task_id" => $arTask["ID"], "action" => "view"));
			else
				$p1 = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_TASKS_TASK"], array("user_id" => $arTask["OwnerId"], "task_id" => $arTask["ID"], "action" => "view"));

			$taskDates = "";
			if (StrLen($arTaskFields["DATE_ACTIVE_FROM"]) > 0 && StrLen($arTaskFields["DATE_ACTIVE_TO"]) > 0)
				$taskDates = $arTaskFields["DATE_ACTIVE_FROM"]." - ".$arTaskFields["DATE_ACTIVE_TO"];
			elseif (StrLen($arTaskFields["DATE_ACTIVE_FROM"]) <= 0 && StrLen($arTaskFields["DATE_ACTIVE_TO"]) > 0)
				$taskDates = Str_Replace("#DATE#", $arTaskFields["DATE_ACTIVE_TO"], GetMessage("INTASK_TO_DATE_TLP"));
			elseif (StrLen($arTaskFields["DATE_ACTIVE_FROM"]) > 0 && StrLen($arTaskFields["DATE_ACTIVE_TO"]) <= 0)
				$taskDates = Str_Replace("#DATE#", $arTaskFields["DATE_ACTIVE_FROM"], GetMessage("INTASK_FROM_DATE_TLP"));

//$hFileTmp = fopen($_SERVER["DOCUMENT_ROOT"]."/+++++++.+++", "a");  // DUMPING
//fwrite($hFileTmp, ":\n".print_r($arTask, true)."\n");
//fclose($hFileTmp);

			$arTask["TASKSTATUS_PRINTABLE"] = $currentTaskStatus;

			$arResult["Tasks"][] = array(
				"FIELDS" => $arTask,
				"ACTIONS" => $arActions,
				"VIEW_URL" => $p1.(StrPos($p1, "?") === false ? "?" : "&").$strUrlAppendix,
				//"IS_CURRENT_USER_TASK" => ($currentTaskAssignedToUser == $GLOBALS["USER"]->GetID()),
				"TASK_STATUS" => $currentTaskStatusId,
				"TASK_PRIORITY" => $currentTaskPriority,
				//"TASK_ALT" => $taskDates.(StrLen($taskDates) > 0 ? "\n" : "").$arTaskFields["NAME"]."\n\n".htmlspecialcharsbx($arTaskFields["~DETAIL_TEXT"]),
				"COMMENTS" => $arTaskProps["FORUM_MESSAGE_CNT"]["VALUE"],
			);
		}

		$arResult["NAV_STRING"] = $dbTasksList1->GetPageNavStringEx($navComponentObject, GetMessage("INTS_TASKS_NAV"), "", false);
		$arResult["NAV_CACHED_DATA"] = $navComponentObject->GetTemplateCachedData();
		$arResult["NAV_RESULT"] = $dbTasksList;
	}

	$BX_MESS = array(
		'EditFolderTitle' => GetMessage("INTS_MESS_EDIT_FOLDER"),
		'NewFolderTitle' => GetMessage("INTS_MESS_NEW_FOLDER"),
		'FolderNameErr' => GetMessage("INTS_MESS_NAME_ERR"),
		'FolderSaveErr' => GetMessage("INTS_MESS_SAVE_ERR"),
		'DelFolderConfirm' => GetMessage("INTS_MESS_DEL_CONF"),
	);
	$arResult['BX_MESS'] = CUtil::PhpToJSObject($BX_MESS);

	$JSConfig = array(
		'page' => $APPLICATION->GetCurPageParam("", array()),
		'iblockId' => $iblockId,
		'taskType' => $taskType,
		'ownerId' => $ownerId,
		'userSessId' => bitrix_sessid_get(),
		'parentSectionId' => ((is_array($arSectionsChain) && count($arSectionsChain) > 0) ? $arSectionsChain[count($arSectionsChain) - 1]["ID"] : $globalParentSectionId),
	);
	$arResult['JSConfig'] = CUtil::PhpToJSObject($JSConfig);

	$arResult['JSEvents'] = '[]';
}
//echo "<pre>".print_r($arResult, true)."</pre>";

$this->IncludeComponentTemplate();
?>