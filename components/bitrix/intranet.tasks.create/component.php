<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("iblock"))
	return ShowError(GetMessage("EC_IBLOCK_MODULE_NOT_INSTALLED"));
if (!CModule::IncludeModule("socialnetwork"))
	return ShowError(GetMessage("EC_SONET_MODULE_NOT_INSTALLED"));

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$GLOBALS["DBType"]."/favorites.php");
include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/intranet/classes/general/tasks_document.php");
include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/intranet/classes/general/tasks.php");

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

$arParams["VIEW_VAR"] = trim($arParams["VIEW_VAR"]);
if (strlen($arParams["VIEW_VAR"]) <= 0)
	$arParams["VIEW_VAR"] = "view_id";

$arParams["ACTION_VAR"] = trim($arParams["ACTION_VAR"]);
if (strlen($arParams["ACTION_VAR"]) <= 0)
	$arParams["ACTION_VAR"] = "action";

if (strlen($arParams["PAGE_VAR"]) <= 0)
	$arParams["PAGE_VAR"] = "page";

$arParams["TASK_ID"] = intval($arParams["TASK_ID"]);

$arParams["ACTION"] = strtolower(trim($arParams["ACTION"]));
if (strlen($arParams["ACTION"]) <= 0 || !in_array($arParams["ACTION"], array("create", "edit", "view")))
	$arParams["ACTION"] = ($arParams["TASK_ID"] > 0 ? "view" : "create");
if (in_array($arParams["ACTION"], array("edit", "view")) && $arParams["TASK_ID"] <= 0)
	$arParams["ACTION"] = "create";

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
	
$arResult["back_url"] = htmlspecialcharsbx(trim($_REQUEST["back_url"]));

if ($taskType == "user")
{
	$arParams["PATH_TO_TASKS"] = str_replace("#user_id#", "#owner_id#", $arParams["PATH_TO_USER_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#user_id#", "#owner_id#", $arParams["PATH_TO_USER_TASKS_TASK"]);
}
else
{
	$arParams["PATH_TO_TASKS"] = str_replace("#group_id#", "#owner_id#", $arParams["PATH_TO_GROUP_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#group_id#", "#owner_id#", $arParams["PATH_TO_GROUP_TASKS_TASK"]);
}

if (strlen($arResult["back_url"]) <= 0)
	$arResult["back_url"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array("owner_id" => $ownerId));

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
			$arResult["FatalError"] .= GetMessage("INTE_TASKS_OFF").".";
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		if (!CIntranetTasks::CanCurrentUserPerformOperation($taskType, $ownerId, "view"))
			$arResult["FatalError"] .= GetMessage("INTE_NO_SONET_PERMS").".";
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		if ($arParams["ACTION"] == "create")
		{
			if (!CIntranetTasks::CanCurrentUserPerformOperation($taskType, $ownerId, "create_tasks"))
				$arResult["FatalError"] .= GetMessage("INTE_NO_CREATE_PERMS").".";
		}
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		$globalParentSectionId = CIntranetTasks::InitializeIBlock($taskType, $ownerId, $arParams["FORUM_ID"]);
		if ($globalParentSectionId <= 0)
			$arResult["FatalError"] .= GetMessage("INTE_TASK_NOT_FOUND").". ";
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		$arResult["Task"] = false;
		if ($arParams["TASK_ID"] > 0)
		{
			$arResult["Task"] = CIntranetTasks::GetById(
				$arParams["TASK_ID"],
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
				)
			);
			if (!$arResult["Task"] || $arResult["Task"]["ROOT_SECTION_ID"] != $globalParentSectionId)
				$arResult["FatalError"] .= GetMessage("INTE_TASK_NOT_FOUND").".";
		}
		else
		{
			$arResult["Task"]["IBLOCK_SECTION_ID"] = intval($_REQUEST["flt_iblock_section"]);
			$arResult["Task"]["DATE_ACTIVE_FROM"] = date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME));
			$arResult["Task"]["DATE_ACTIVE_FROM_PRINTABLE"] = $arResult["Task"]["DATE_ACTIVE_FROM"];
			if ($taskType == "user")
				$arResult["Task"]["PROPERTY_TaskAssignedTo"] = $ownerId;
			else
				$arResult["Task"]["PROPERTY_TaskAssignedTo"] = $USER->GetID();
		}
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array("intranet", "CIntranetTasksDocument", "x".$iblockId),
			($arParams["TASK_ID"] > 0) ? array("intranet", "CIntranetTasksDocument", $arParams["TASK_ID"]) : null
		);

		$arCurrentUserGroups = array();

		if ($taskType == "group")
		{
			$arCurrentUserGroups[] = SONET_ROLES_ALL;

			if ($GLOBALS["USER"]->IsAuthorized())
				$arCurrentUserGroups[] = SONET_ROLES_AUTHORIZED;

			$r = CSocNetUserToGroup::GetUserRole($USER->GetID(), $ownerId);
			if (strlen($r) > 0)
				$arCurrentUserGroups[] = $r;
		}
		else
		{
//			$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_ALL;
//			if (CSocNetUserRelations::IsFriends($USER->GetID(), $ownerId))
//				$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_FRIENDS;
//			elseif (CSocNetUserRelations::IsFriends2($USER->GetID(), $ownerId))
//				$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_FRIENDS2;
		}

		if ($arParams["TASK_ID"] > 0)
		{
			if ($USER->GetID() == $arResult["Task"]["CREATED_BY"])
				$arCurrentUserGroups[] = "author";
			if ($USER->GetID() == $arResult["Task"]["PROPERTY_TaskAssignedTo"])
				$arCurrentUserGroups[] = "responsible";
			if (is_array($arResult["Task"]["PROPERTY_TaskTrackers"]) && in_array($USER->GetID(), $arResult["Task"]["PROPERTY_TaskTrackers"]))
				$arCurrentUserGroups[] = "trackers";
		}
		else
		{
			$arCurrentUserGroups[] = "author";
		}

		$canAccess = false;
		if ($arParams["TASK_ID"] > 0)
		{
			$canAccess = CIntranetTasksDocument::CanUserOperateDocument(
				$arParams["ACTION"] == "view" ? INTASK_DOCUMENT_OPERATION_READ_DOCUMENT : INTASK_DOCUMENT_OPERATION_WRITE_DOCUMENT,
				$GLOBALS["USER"]->GetID(),
				$arParams["TASK_ID"],
				array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
			);
		}
		else
		{
			$canAccess = CIntranetTasksDocument::CanUserOperateDocumentType(
				INTASK_DOCUMENT_OPERATION_WRITE_DOCUMENT,
				$GLOBALS["USER"]->GetID(),
				$taskType."_".$ownerId,
				array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
			);
		}

		if (!$canAccess)
			$arResult["FatalError"] .= GetMessage("INTE_NO_SONET_PERMS").".";
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		$arResult["TaskFields"] = CIntranetTasks::GetTaskFields($taskType, $ownerId);
		$arResult["bVarsFromForm"] = false;

		if ($arParams["TASK_ID"] <= 0)
		{
			$a = array_keys($arResult["TaskFields"]["PROPERTY_TaskPriority"]["Options"]);
			$arResult["Task"]["PROPERTY_TaskPriority"] = $a[1];
		}

		if ($_SERVER["REQUEST_METHOD"] == "POST" 
			&& (StrLen($_POST["save"]) > 0 || StrLen($_POST["apply"]) > 0)
			&& check_bitrix_sessid() 
			&& ($arParams["ACTION"] == "create" || $arParams["ACTION"] == "edit"))
		{
			$arResult["ErrorMessage"] = "";

			$arFields = array("MODIFIED_BY" => $GLOBALS["USER"]->GetID());

			foreach ($arResult["TaskFields"] as $fieldKey => $arField)
			{
				if (array_key_exists($fieldKey, $_POST))
				{
					if ($arField["Type"] == "user")
					{
						$u = $_POST[$fieldKey];
						if (!is_array($u))
							$u = array($u);

						$r = array();
						foreach ($u as $u1)
						{
							$arFoundUsers = CSocNetUser::SearchUser($u1, true);
							if ($arFoundUsers && is_array($arFoundUsers) && count($arFoundUsers) > 0)
							{
								foreach ($arFoundUsers as $userID => $userName)
									$r[] = intval($userID);
							}
						}

						if (is_array($_POST[$fieldKey]))
							$arFields[$fieldKey] = $r;
						else
							$arFields[$fieldKey] = count($r) > 0 ? $r[0] : "";
					}
					else
					{
						$arFields[$fieldKey] = $_POST[$fieldKey];
					}
				}
			}

			if (array_key_exists("IBLOCK_SECTION_ID", $arFields))
			{
				if (!array_key_exists($arFields["IBLOCK_SECTION_ID"], $arResult["TaskFields"]["IBLOCK_SECTION_ID"]["Options"]))
					$arFields["IBLOCK_SECTION_ID"] = $globalParentSectionId;
			}
			else
			{
				$arFields["IBLOCK_SECTION_ID"] = $globalParentSectionId;
			}

//$hFileTmp = fopen($_SERVER["DOCUMENT_ROOT"]."/+++++++.+++", "a");  // DUMPING
//fwrite($hFileTmp, "_FILES:\n".print_r($_FILES, true)."\n");
//fclose($hFileTmp);

			if (is_array($_FILES))
			{
				foreach ($_FILES as $k1 => $v1)
				{
					foreach ($v1["error"] as $i => $err)
					{
//						if ($err == 0)
//						{
							if (!is_array($arFields[$k1]))
								$arFields[$k1] = array();

							foreach ($v1 as $k2 => $v2)
							{
								if (!is_array($arFields[$k1][$i]))
									$arFields[$k1][$i] = array();
								$arFields[$k1][$i][$k2] = $v2[$i];
							}
//						}
					}

					if (array_key_exists($k1."_del", $_POST))
					{
						if (is_array($_POST[$k1."_del"]))
						{
							foreach ($_POST[$k1."_del"] as $k4 => $v4)
								$arFields[$k1][$k4]["del"] = $v4;
						}
						else
						{
							$arFields[$k1]["del"] = $_POST[$k1."_del"];
						}
					}
				}
			}

			if (!array_key_exists("PROPERTY_TaskAssignedTo", $arFields))
				$arFields["PROPERTY_TaskAssignedTo"] = $arResult["Task"]["PROPERTY_TaskAssignedTo"];

			if (array_key_exists("bizproc_event", $_POST) && strlen($_POST["bizproc_event"]) > 0)
			{
				$arBizProcEvent = explode("_", $_POST["bizproc_event"]);
				if (count($arBizProcEvent) != 3)
				{
					$_POST["bizproc_event"] = "";
				}
				else
				{
					$bizProcEvent = $arBizProcEvent[2];
					if ($bizProcEvent == "SetResponsibleEvent" || $arParams["ACTION"] == "create")
					{
						
					}
					else
					{
						$arFields["PROPERTY_TaskAssignedTo"] = $arResult["Task"]["PROPERTY_TaskAssignedTo"];
					}
				}
			}

			$taskAssignedToUserId = 0;
			if (array_key_exists("PROPERTY_TaskAssignedTo", $arFields))
				$taskAssignedToUserId = intval($arFields["PROPERTY_TaskAssignedTo"]);

			foreach ($arResult["TaskFields"] as $fieldKey => $arField)
			{
				if ($arField["Required"])
				{
					if (!array_key_exists($fieldKey, $arFields)
						|| (!is_array($arFields[$fieldKey]) && strlen($arFields[$fieldKey]) <= 0)
						|| (is_array($arFields[$fieldKey]) && count($arFields[$fieldKey]) <= 0))
					{
						$arResult["ErrorMessage"] .= str_replace("#FIELD#", $arField["Name"], GetMessage("INTE_TASKS_EMPTY_FIELD"))."<br />";
					}
				}
			}

			if (strlen($arResult["ErrorMessage"]) <= 0)
			{
				$arErrorsTmp = array();

				if ($arParams["ACTION"] == "create")
					$arParams["TASK_ID"] = CIntranetTasks::Add($arFields, $arErrorsTmp);
				else
					CIntranetTasks::Update($arParams["TASK_ID"], $arFields, $arErrorsTmp);

				if (count($arErrorsTmp) > 0)
				{
					foreach ($arErrorsTmp as $e)
						$arResult["ErrorMessage"] .= $e["message"]."<br />";
				}
			}

			if (strlen($arResult["ErrorMessage"]) <= 0)
			{
				$arWorkflowIds = array();

				if ($taskType == "group")
				{
					$pathTemplate = str_replace(
						array("#GROUP_ID#", "#TASK_ID#"),
						array($ownerId, "{=Document:ID}"),
						COption::GetOptionString("intranet", "path_task_group_entry", "/workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/")
					);
				}
				else
				{
					$pathTemplate = str_replace(
						array("#USER_ID#", "#TASK_ID#"),
						array($ownerId, "{=Document:ID}"),
						COption::GetOptionString("intranet", "path_task_user_entry", "/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/")
					);
				}
				$pathTemplate = str_replace('#HTTP_HOST#', $_SERVER['HTTP_HOST'], "http://#HTTP_HOST#".$pathTemplate);

				foreach ($arDocumentStates as $arDocumentState)
				{
					if (strlen($arDocumentState["ID"]) <= 0)
					{
						$arErrorsTmp = array();

						$arWorkflowIds[$arDocumentState["TEMPLATE_ID"]] = CBPDocument::StartWorkflow(
							$arDocumentState["TEMPLATE_ID"],
							array("intranet", "CIntranetTasksDocument", $arParams["TASK_ID"]),
							array(
								"OwnerId" => $ownerId,
								"TaskType" => $taskType,
								"PathTemplate" => $pathTemplate,
								"ForumId" => intval($arParams["FORUM_ID"]),
								"IBlockId" => intval($arParams["IBLOCK_ID"]),
							),
							$arErrorsTmp
						);

						if (count($arErrorsTmp) > 0)
						{
							foreach ($arErrorsTmp as $e)
								$arResult["ErrorMessage"] .= $e["message"]."<br />";
						}
					}
				}

				if (array_key_exists("bizproc_event", $_POST) && strlen($_POST["bizproc_event"]) > 0)
				{
					$arErrorsTmp = array();

					foreach ($arDocumentStates as $arDocumentState)
					{
						CBPDocument::SendExternalEvent(
							strlen($arDocumentState["ID"]) <= 0 ? $arWorkflowIds[$arDocumentState["TEMPLATE_ID"]] : $arDocumentState["ID"],
							$_POST["bizproc_event"],
							array("Groups" => $arCurrentUserGroups, "User" => $GLOBALS["USER"]->GetID()),
							$arErrorTmp
						);

						if (count($arErrorsTmp) > 0)
						{
							foreach ($arErrorsTmp as $e)
								$arResult["ErrorMessage"] .= $e["message"]."<br />";
						}

						$arBizProcEvent = explode("_", $_POST["bizproc_event"]);
						if (count($arBizProcEvent) == 3)
						{
							if ($arBizProcEvent[2] == "SetResponsibleEvent" && $GLOBALS["USER"]->GetID() == $taskAssignedToUserId)
							{
								CBPDocument::SendExternalEvent(
									strlen($arDocumentState["ID"]) <= 0 ? $arWorkflowIds[$arDocumentState["TEMPLATE_ID"]] : $arDocumentState["ID"],
									"HEEA_NotAccepted_ApproveEvent",
									array("Groups" => $arCurrentUserGroups, "User" => $GLOBALS["USER"]->GetID()),
									$arErrorTmp
								);

								if (count($arErrorsTmp) > 0)
								{
									foreach ($arErrorsTmp as $e)
										$arResult["ErrorMessage"] .= $e["message"]."<br />";
								}
							}
						}
					}
				}
				else
				{
					if ($arParams["ACTION"] == "create" && $GLOBALS["USER"]->GetID() == $taskAssignedToUserId)
					{
						CBPDocument::SendExternalEvent(
							strlen($arDocumentState["ID"]) <= 0 ? $arWorkflowIds[$arDocumentState["TEMPLATE_ID"]] : $arDocumentState["ID"],
							"HEEA_NotAccepted_ApproveEvent",
							array("Groups" => $arCurrentUserGroups, "User" => $GLOBALS["USER"]->GetID()),
							$arErrorTmp
						);

						if (count($arErrorsTmp) > 0)
						{
							foreach ($arErrorsTmp as $e)
								$arResult["ErrorMessage"] .= $e["message"]."<br />";
						}
					}
				}

				$arDocumentStates = null;
			}

			if (strlen($arResult["ErrorMessage"]) <= 0)
			{
				if ($arParams["ACTION"] == "create")
					CIntranetUtils::UpdateOWSVersion($iblockId, $arParams["TASK_ID"], 1);
				else
					CIntranetUtils::UpdateOWSVersion($iblockId, $arParams["TASK_ID"]);
			}

			if (strlen($arResult["ErrorMessage"]) > 0)
			{
				$arResult["bVarsFromForm"] = true;
			}
			else
			{
				if (array_key_exists("PROPERTY_TaskRemind", $arFields) && strlen($arFields["PROPERTY_TaskRemind"]) > 0)
				{
					CAgent::RemoveAgent("CIntranetTasks::SendRemindEventAgentNew(".$arParams["TASK_ID"].");", "intranet");
					CAgent::AddAgent("CIntranetTasks::SendRemindEventAgentNew(".$arParams["TASK_ID"].");", "intranet", "Y", 10, "", "Y", $arFields["PROPERTY_TaskRemind"]);
				}
				else
				{
					CAgent::RemoveAgent("CIntranetTasks::SendRemindEventAgentNew(".$arParams["TASK_ID"].");", "intranet");
				}

				if (strlen($_POST["save"]) > 0)
				{
					if (array_key_exists("back_url", $_REQUEST) && strlen($_REQUEST["back_url"]) > 0)
						$redirectPath = $_REQUEST["back_url"];
					else
						$redirectPath = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array("owner_id" => $ownerId));
				}
				else
				{
					$redirectPath = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("owner_id" => ($taskType == "user" ? $taskAssignedToUserId : $ownerId), "task_id" => $arParams["TASK_ID"], "action" => "edit"));
				}

				LocalRedirect($redirectPath);
			}
		}
		elseif ($_SERVER["REQUEST_METHOD"] == "POST" 
			&& (StrLen($_POST["save"]) > 0 || StrLen($_POST["apply"]) > 0)
			&& check_bitrix_sessid() 
			&& ($arParams["ACTION"] == "view"))
		{
			$arResult["ErrorMessage"] = "";

			$detailText = $arResult["Task"]["DETAIL_TEXT"];

			$bTextModified = false;
			if (preg_match_all("#(\s(checked)\s+)?name=\"bx_subtask_(\d+)\"#i", $detailText, $arMatches, PREG_SET_ORDER))
			{
				for ($i = 0, $cnt = count($arMatches); $i < $cnt; $i++)
				{
					$matchId = intval($arMatches[$i][3]);
					$oldVal = (strtolower($arMatches[$i][2]) == "checked" ? "Y" : "N");
					$newVal = (array_key_exists("bx_subtask_".$matchId, $_POST) && (intval($_POST["bx_subtask_".$matchId]) == 1 || strtolower($_POST["bx_subtask_".$matchId]) == "on") ? "Y" : "N");

					if ($oldVal != $newVal)
					{
						$bTextModified = true;
						if ($newVal == "Y")
							$detailText = preg_replace("#name=\"bx_subtask_".$matchId."\"#i", "checked name=\"bx_subtask_".$matchId."\"", $detailText);
						else
							$detailText = preg_replace("#checked\s+name=\"bx_subtask_".$matchId."\"#i", "name=\"bx_subtask_".$matchId."\"", $detailText);
					}
				}
			}

			if (array_key_exists("bizproc_event", $_POST) && strlen($_POST["bizproc_event"]) > 0)
			{
				$arBizProcEvent = explode("_", $_POST["bizproc_event"]);
				if (count($arBizProcEvent) != 3)
				{
					$_POST["bizproc_event"] = "";
				}
				else
				{
					$ae = array();
					foreach ($arDocumentStates as $ds)
					{
						$ae = CBPDocument::GetAllowableEvents($GLOBALS["USER"]->GetID(), $arCurrentUserGroups, $ds);
						break;
					}

					$bCanSendEvent = false;
					foreach ($ae as $ae1)
					{
						if ($ae1["NAME"] == $_POST["bizproc_event"])
						{
							$bCanSendEvent = true;
							break;
						}
					}
					if (!$bCanSendEvent)
						$arResult["ErrorMessage"] .= GetMessage("INTE_TASKS_PERMS_EVENT").".<br />";

					if (strlen($arResult["ErrorMessage"]) <= 0)
					{
						$taskAssignedToUserId = 0;

						$bizProcEvent = $arBizProcEvent[2];
						if ($bizProcEvent == "SetResponsibleEvent")
						{
							$arFields = array("MODIFIED_BY" => $GLOBALS["USER"]->GetID());
							if ($bTextModified)
								$arFields["DETAIL_TEXT"] = $detailText;
							$bTextModified = false;

							$u = $_POST["PROPERTY_TaskAssignedTo"];
							if (is_array($u))
							{
								if (count($u) > 0)
								{
									$u1 = array_keys($u);
									$u = $u[$u1[0]];
								}
								else
								{
									$u = "";
								}
							}

							$r = array();
							$arFoundUsers = CSocNetUser::SearchUser($u, true);
							if ($arFoundUsers && is_array($arFoundUsers) && count($arFoundUsers) > 0)
							{
								foreach ($arFoundUsers as $userID => $userName)
									$r[] = intval($userID);
							}

							$arFields["PROPERTY_TaskAssignedTo"] = count($r) > 0 ? $r[0] : 0;

							if ($arFields["PROPERTY_TaskAssignedTo"] <= 0)
								$arResult["ErrorMessage"] .= str_replace("#FIELD#", GetMessage("INTE_TASKS_RESPONSIBLE"), GetMessage("INTE_TASKS_EMPTY_FIELD"))."<br />";

							if (strlen($arResult["ErrorMessage"]) <= 0)
							{
								$arErrorsTmp = array();
								CIntranetTasks::Update($arParams["TASK_ID"], $arFields, $arErrorsTmp);
								if (count($arErrorsTmp) > 0)
								{
									foreach ($arErrorsTmp as $e)
										$arResult["ErrorMessage"] .= $e["message"]."<br />";
								}
								else
								{
									$taskAssignedToUserId = $arFields["PROPERTY_TaskAssignedTo"];
								}
							}
						}

						if ($taskAssignedToUserId <= 0)
							$taskAssignedToUserId = $arResult["Task"]["PROPERTY_TaskAssignedTo"];
					}

					if (strlen($arResult["ErrorMessage"]) <= 0)
					{
						$arErrorsTmp = array();

						foreach ($arDocumentStates as $arDocumentState)
						{
							CBPDocument::SendExternalEvent(
								$arDocumentState["ID"],
								$_POST["bizproc_event"],
								array("Groups" => $arCurrentUserGroups, "User" => $GLOBALS["USER"]->GetID()),
								$arErrorTmp
							);

							if (count($arErrorsTmp) > 0)
							{
								foreach ($arErrorsTmp as $e)
									$arResult["ErrorMessage"] .= $e["message"]."<br />";
							}

							if ($bizProcEvent == "SetResponsibleEvent" && $GLOBALS["USER"]->GetID() == $taskAssignedToUserId)
							{
								CBPDocument::SendExternalEvent(
									$arDocumentState["ID"],
									"HEEA_NotAccepted_ApproveEvent",
									array("Groups" => $arCurrentUserGroups, "User" => $GLOBALS["USER"]->GetID()),
									$arErrorTmp
								);

								if (count($arErrorsTmp) > 0)
								{
									foreach ($arErrorsTmp as $e)
										$arResult["ErrorMessage"] .= $e["message"]."<br />";
								}
							}
						}
					}
				}
			}

			if ($bTextModified)
			{
				$arFields = array("MODIFIED_BY" => $GLOBALS["USER"]->GetID(), "DETAIL_TEXT" => $detailText);

				$arErrorsTmp = array();
				CIntranetTasks::Update($arParams["TASK_ID"], $arFields, $arErrorsTmp);
				if (count($arErrorsTmp) > 0)
				{
					foreach ($arErrorsTmp as $e)
						$arResult["ErrorMessage"] .= $e["message"]."<br />";
				}
			}

			if (strlen($arResult["ErrorMessage"]) <= 0)
			{
				if (strlen($_POST["save"]) > 0)
				{
					if (array_key_exists("back_url", $_REQUEST) && strlen($_REQUEST["back_url"]) > 0)
						$redirectPath = $_REQUEST["back_url"];
					else
						$redirectPath = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array("owner_id" => $ownerId));
				}
				else
				{
					$redirectPath = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("owner_id" => ($taskType == "user" ? $taskAssignedToUserId : $ownerId), "task_id" => $arParams["TASK_ID"], "action" => "view"));
				}

				LocalRedirect($redirectPath);
			}
		}

		if ($arResult["bVarsFromForm"])
		{
			$isInSecurity = CModule::IncludeModule("security");

			foreach ($arResult["TaskFields"] as $fieldKey => $arField)
			{
				if ($arField["Type"] == "file")
					continue;

				if (array_key_exists($fieldKey, $arFields))
				{
					if ($arField["Type"] == "select")
					{
						$arResult["Task"][$fieldKey] = array();
						if (is_array($arFields[$fieldKey]))
						{
							foreach ($arFields[$fieldKey] as $v)
							{
								if (array_key_exists($v, $arField["Options"]))
									$arResult["Task"][$fieldKey][$v] = $arField["Options"][$v];
							}
						}
						else
						{
							if (array_key_exists($arFields[$fieldKey], $arField["Options"]))
								$arResult["Task"][$fieldKey][$arFields[$fieldKey]] = $arField["Options"][$arFields[$fieldKey]];
						}
						$arResult["Task"][$fieldKey."_PRINTABLE"] = $arResult["Task"][$fieldKey];
					}
					elseif ($arField["Type"] == "text")
					{
						if ($isInSecurity)
						{
							$filter = new CSecurityFilter;
							if (is_array($arFields[$fieldKey]))
							{
								foreach ($arFields[$fieldKey] as $k => $v)
									$arResult["Task"][$fieldKey][$k] = $filter->TestXSS($v);
							}
							else
							{
								$arResult["Task"][$fieldKey] = $filter->TestXSS($arFields[$fieldKey]);
							}
						}
						else
						{
							if (is_array($arFields[$fieldKey]))
							{
								foreach ($arFields[$fieldKey] as $k => $v)
									$arResult["Task"][$fieldKey][$k] = htmlspecialcharsbx($v);
							}
							else
							{
								$arResult["Task"][$fieldKey] = htmlspecialcharsbx($arFields[$fieldKey]);
							}
						}
						$arResult["Task"][$fieldKey."_PRINTABLE"] = $arResult["Task"][$fieldKey];
					}
					else
					{
						if (is_array($arFields[$fieldKey]))
						{
							foreach ($arFields[$fieldKey] as $k => $v)
							{
								$arResult["Task"][$fieldKey][$k] = htmlspecialcharsbx($v);
								$arResult["Task"][$fieldKey."_PRINTABLE"][$k] = htmlspecialcharsbx($v);
							}
						}
						else
						{
							$arResult["Task"][$fieldKey] = htmlspecialcharsbx($arFields[$fieldKey]);
							$arResult["Task"][$fieldKey."_PRINTABLE"] = htmlspecialcharsbx($arFields[$fieldKey]);
						}
					}
				}
			}
		}
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		if (!$arDocumentStates)
		{
			$arDocumentStates = CBPDocument::GetDocumentStates(
				array("intranet", "CIntranetTasksDocument", "x".$iblockId),
				($arParams["TASK_ID"] > 0) ? array("intranet", "CIntranetTasksDocument", $arParams["TASK_ID"]) : null
			);
		}

		$kk = array_keys($arDocumentStates);
		foreach ($kk as $k)
		{
			$arResult["DocumentState"] = $arDocumentStates[$k];
			$arResult["DocumentState"]["AllowableEvents"] = CBPDocument::GetAllowableEvents($GLOBALS["USER"]->GetID(), $arCurrentUserGroups, $arDocumentStates[$k]);

			if ($arParams["ACTION"] == "create")
			{
				$kk1 = array_keys($arResult["DocumentState"]["AllowableEvents"]);
				foreach ($kk1 as $k1)
				{
					if ($arResult["DocumentState"]["AllowableEvents"][$k1]["NAME"] == "HEEA_NotAccepted_SetResponsibleEvent")
						unset($arResult["DocumentState"]["AllowableEvents"][$k1]);
				}
			}
		}
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		$arResult["TaskFieldsOrder"] = array();
		foreach ($arResult["TaskFields"] as $key => $value)
			$arResult["TaskFieldsOrder"][] = array(0 => $key, 1 => $value["PSORT"]);

		for ($i = 0; $i < count($arResult["TaskFieldsOrder"]) - 1; $i++)
		{
			for ($j = $i + 1; $j < count($arResult["TaskFieldsOrder"]); $j++)
			{
				if ($arResult["TaskFieldsOrder"][$i][1] > $arResult["TaskFieldsOrder"][$j][1])
				{
					$t = $arResult["TaskFieldsOrder"][$i];
					$arResult["TaskFieldsOrder"][$i] = $arResult["TaskFieldsOrder"][$j];
					$arResult["TaskFieldsOrder"][$j] = $t;
				}
			}
		}
	}

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

		$strTitle = "";
		if ($arParams["ACTION"] == "create")
			$strTitle = GetMessage("INTE_CREATE_TITLE");
		elseif ($arParams["ACTION"] == "edit")
			$strTitle = str_replace("#ID#", $arParams["TASK_ID"], GetMessage("INTE_EDIT_TITLE"));
		else
			$strTitle = str_replace("#ID#", $arParams["TASK_ID"], GetMessage("INTE_VIEW_TITLE"));

		if ($taskType == "user" && ($arParams["SET_TITLE"] == "Y" || $arParams["SET_NAVCHAIN"] != "N"))	
		{
			if (strlen($arParams["NAME_TEMPLATE"]) <= 0)		
				$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
				
			$arParams["TITLE_NAME_TEMPLATE"] = str_replace(
				array("#NOBR#", "#/NOBR#"), 
				array("", ""), 
				$arParams["NAME_TEMPLATE"]
			);

			$arTmpUser = array(
						'NAME' => $arResult["Owner"]["~NAME"],
						'LAST_NAME' => $arResult["Owner"]["~LAST_NAME"],
						'SECOND_NAME' => $arResult["Owner"]["~SECOND_NAME"],
						'LOGIN' => $arResult["Owner"]["~LOGIN"],
					);
			$bUseLogin = $arParams['HIDE_LOGIN'] == "Y" ? false : true;							
			$strTitleFormatted = CUser::FormatName($arParams['TITLE_NAME_TEMPLATE'], $arTmpUser, $bUseLogin);
		}
		
		if ($arParams["SET_TITLE"] == "Y")
		{
			if ($taskType == "user")
				$APPLICATION->SetTitle($strTitleFormatted.": ".$strTitle);
			else
				$APPLICATION->SetTitle($arResult["Owner"]["NAME"].": ".$strTitle);
		}

		if ($arParams["SET_NAVCHAIN"] != "N")
		{
			if ($taskType == "user")
			{
				$APPLICATION->AddChainItem($strTitleFormatted, CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_USER"], array("user_id" => $arParams["OWNER_ID"])));
				$APPLICATION->AddChainItem($strTitle);
			}
			else
			{
				$APPLICATION->AddChainItem($arResult["Owner"]["NAME"], CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_GROUP"], array("group_id" => $arParams["OWNER_ID"])));
				$APPLICATION->AddChainItem($strTitle);
			}
		}
	}
}

$arResult["IsInSecurity"] = CModule::IncludeModule("security");

$this->IncludeComponentTemplate();

$GroupArchive = false;
if ($taskType != "user" && array_key_exists("Owner", $arResult) && array_key_exists("CLOSED", $arResult["Owner"]) && $arResult["Owner"]["CLOSED"] == "Y")
	$GroupArchive = true;

return (StrLen($arResult["FatalError"]) <= 0 && $arParams["TASK_ID"] > 0 && $arParams["ACTION"] == "view" && !$GroupArchive);
?>