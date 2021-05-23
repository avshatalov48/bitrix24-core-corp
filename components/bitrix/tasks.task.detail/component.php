<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_INSTALLED"));
	return;
}

if (!CModule::IncludeModule("forum"))
{
	ShowError(GetMessage("FORUM_MODULE_NOT_INSTALLED"));
	return;
}
if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SOCNET_MODULE_NOT_INSTALLED"));
	return;
}

global $USER, $APPLICATION;

__checkForum($arParams["FORUM_ID"]);

try
{
	$arResult['FORUM_ID'] = CTasksTools::getForumIdForIntranet();		// can be overrided below by fact FORUM_ID attached to task
}
catch(Exception $e)
{
	$arResult['FORUM_ID'] = 0;
}

$arParams["TASK_VAR"] = trim($arParams["TASK_VAR"]);
if (strlen($arParams["TASK_VAR"]) <= 0)
	$arParams["TASK_VAR"] = "task_id";

$arParams["GROUP_VAR"] = isset($arParams["GROUP_VAR"]) ? trim($arParams["GROUP_VAR"]) : "";
if (strlen($arParams["GROUP_VAR"]) <= 0)
	$arParams["GROUP_VAR"] = "group_id";

$arParams["ACTION_VAR"] = trim($arParams["ACTION_VAR"]);
if (strlen($arParams["ACTION_VAR"]) <= 0)
	$arParams["ACTION_VAR"] = "action";

if (strlen($arParams["PAGE_VAR"]) <= 0)
	$arParams["PAGE_VAR"] = "page";

$arParams["TASK_ID"] = intval($arParams["TASK_ID"]);
if (!$arParams["TASK_ID"])
{
	ShowError(GetMessage("TASKS_BAD_TASK_ID"));
	return;
}

$loggedInUserId = (int) $USER->getId();

$arParams["USER_ID"] = intval($arParams["USER_ID"]) > 0 ? intval($arParams["USER_ID"]) : $loggedInUserId;

$arParams["GROUP_ID"] = isset($arParams["GROUP_ID"]) ? intval($arParams["GROUP_ID"]) : 0;

$arResult["TASK_TYPE"] = $taskType = ($arParams["GROUP_ID"] > 0 ? "group" : "user");

$arResult["IS_IFRAME"] = (isset($_GET["IFRAME"]) && $_GET["IFRAME"] == "Y");
if (isset($_GET["CALLBACK"]) && ($_GET["CALLBACK"] == "ADDED" || $_GET["CALLBACK"] == "CHANGED"))
{
	$arResult["CALLBACK"] = $_GET["CALLBACK"];
}

if (isset($_GET['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE']) && ($_GET['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE'] !== ''))
	$arResult['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE'] = array_map('intval', explode(',', $_GET['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE']));

//user paths
$arParams["PATH_TO_USER_TASKS"] = trim($arParams["PATH_TO_USER_TASKS"]);
if (strlen($arParams["PATH_TO_USER_TASKS"]) <= 0)
{
	$arParams["PATH_TO_USER_TASKS"] = COption::GetOptionString("tasks", "paths_task_user", null, SITE_ID);
}
$arParams["PATH_TO_USER_TASKS_TASK"] = trim($arParams["PATH_TO_USER_TASKS_TASK"]);
if (strlen($arParams["PATH_TO_USER_TASKS_TASK"]) <= 0)
{
	$arParams["PATH_TO_USER_TASKS_TASK"] = COption::GetOptionString("tasks", "paths_task_user_action", null, SITE_ID);
}

//group paths
$arParams["PATH_TO_GROUP_TASKS"] = trim($arParams["PATH_TO_GROUP_TASKS"]);
if (strlen($arParams["PATH_TO_GROUP_TASKS"]) <= 0)
{
	$arParams["PATH_TO_GROUP_TASKS"] = COption::GetOptionString("tasks", "paths_task_group", null, SITE_ID);
}
$arParams["PATH_TO_GROUP_TASKS_TASK"] = isset($arParams["PATH_TO_GROUP_TASKS_TASK"]) ? trim($arParams["PATH_TO_GROUP_TASKS_TASK"]) : "";
if (strlen($arParams["PATH_TO_GROUP_TASKS_TASK"]) <= 0)
{
	$arParams["PATH_TO_GROUP_TASKS_TASK"] = COption::GetOptionString("tasks", "paths_task_group_action", null, SITE_ID);
}
$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = trim($arParams["PATH_TO_USER_TASKS_TEMPLATES"]);
if (strlen($arParams["PATH_TO_USER_TASKS_TEMPLATES"]) <= 0)
{
	$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_templates&".$arParams["USER_VAR"]."=#user_id#");
}
$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = trim($arParams["PATH_TO_USER_TASKS_TEMPLATES"]);
$arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"] = trim($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]);
if (strlen($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]) <= 0)
{
	$arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_templates_template&".$arParams["USER_VAR"]."=#user_id#&".$arParams["TEMPLATE_VAR"]."=#template_id#&".$arParams["ACTION_VAR"]."=#action#");
}

$arParams["PATH_TO_TEMPLATES_TEMPLATE"] = str_replace("#user_id#", $loggedInUserId, $arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]);
$arParams["PATH_TO_TASKS_TEMPLATES"] = str_replace("#user_id#", $loggedInUserId, $arParams["PATH_TO_USER_TASKS_TEMPLATES"]);

// Must be equal to MESSAGES_PER_PAGE in mobile.tasks.topic.reviews
if (!isset($arParams["ITEM_DETAIL_COUNT"]))
{
	$arParams["ITEM_DETAIL_COUNT"] = 10;
}

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

if ($taskType == "user")
{
	$arParams["PATH_TO_TASKS"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]);

	$rsUser = CUser::GetByID($arParams["USER_ID"]);
	if ($user = $rsUser->GetNext())
	{
		$arResult["USER"] = $user;
	}
	else
	{
		ShowError(GetMessage("TASKS_USER_NOT_FOUND"));
		return;
	}
}
else
{
	$arParams["PATH_TO_TASKS"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS_TASK"]);

	$arResult["GROUP"] = CSocNetGroup::GetByID($arParams["GROUP_ID"]);
	if (!$arResult["GROUP"])
	{
		return;
	}
}

if (!$arResult["USER"])
{
	$rsUser = CUser::GetByID($loggedInUserId);
	$arResult["USER"] = $rsUser->GetNext();
}

$arResult['MAX_UPLOAD_FILES_IN_COMMENTS'] = (int) COption::GetOptionString('tasks', 'MAX_UPLOAD_FILES_IN_COMMENTS');

$arTask = null;
try
{
	$oTask  = new CTaskItem($arParams['TASK_ID'], $loggedInUserId);
	$arTask = $oTask->getData();

	$arTask['~TAGS']       = $oTask->getTags();
	$arTask['~FILES']      = $arTask['FILES']      = $oTask->getFiles();
	$arTask['~DEPENDS_ON'] = $arTask['DEPENDS_ON'] = $oTask->getDependsOn();

	$arTask['TAGS'] = array_map('htmlspecialcharsbx', $arTask['~TAGS']);

	// Get the fact FORUM_ID from task
	if ($arTask['FORUM_ID'] >= 1)
		$arResult['FORUM_ID'] = $arTask['FORUM_ID'];
}
catch (Exception $e)
{
	$arTask = null;
}

if (isset($_REQUEST["ACTION"]) && check_bitrix_sessid())
{
	if ($arTask)
	{
		$action = $_REQUEST['ACTION'];
		$taskID = $arTask["ID"];

		$redirectTo = null;

		try
		{
			if ($action == "delete")
			{
				if ($_REQUEST["back_url"])
					$redirectTo = $_REQUEST["back_url"];
				else
					$redirectTo = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"]);
				
				$oTask->delete();
			}
			elseif ($action == "elapsed_add")
			{
				$redirectTo = $APPLICATION->GetCurPageParam(RandString(8), array("ACTION", "sessid"))."#elapsed";
				$minutes = ((int) $_POST['HOURS']) * 60 + (int) $_POST['MINUTES'];
				$cdate = $_POST['CREATED_DATE'];
				CTaskElapsedItem::add($oTask, array('MINUTES' => $minutes, 'COMMENT_TEXT' => trim($_POST["COMMENT_TEXT"]), 'CREATED_DATE' => $cdate));
			}
			elseif ($action === 'elapsed_update')
			{
				$seconds = ((int) $_POST['HOURS']) * 3600 + ((int) $_POST['MINUTES']) * 60;
				if (isset($_POST['SECONDS']) && ($_POST['SECONDS'] > 0))
					$seconds += (int) $_POST['SECONDS'];
				$cdate = $_POST['CREATED_DATE'];

				$redirectTo = $APPLICATION->GetCurPageParam("", array("ACTION", "sessid"))."#elapsed";
				$oElapsedItem = new CTaskElapsedItem($oTask, (int) $_POST['ELAPSED_ID']);
				$oElapsedItem->update(array(
					'SECONDS'      => $seconds,
					'COMMENT_TEXT' => trim($_POST["COMMENT_TEXT"]),
					'CREATED_DATE' => $cdate
				));
			}
			elseif ($action === 'elapsed_delete')
			{
				$redirectTo = $APPLICATION->GetCurPageParam("", array("ACTION", "sessid", "ELAPSED_ID"))."#elapsed";
				$oElapsedItem = new CTaskElapsedItem($oTask, (int) $_GET['ELAPSED_ID']);
				$oElapsedItem->delete();
			}
			else
			{
				$arMap = array('close' => 'complete', 'start' => 'startExecution', 'accept' => 'accept', 
					'renew' => 'renew', 'defer' => 'defer', 'decline' => 'decline', 'delegate' => 'delegate',
					'approve' => 'approve', 'disapprove' => 'disapprove');

				if (isset($arMap[$action]))
				{
					$arArgs = array();
					if ($action === 'decline')
						$arArgs = array($_POST['REASON']);
					elseif ($action === 'delegate')
						$arArgs = array($_REQUEST['USER_ID']);

					call_user_func_array(array($oTask, $arMap[$action]), $arArgs);
				}
			}
		}
		catch (Exception $e)
		{
			$errCode = $e->getCode();
			$strError = GetMessage('TASKS_FAILED_TO_DO_ACTION');
			if ($e instanceof TasksException)
			{
				if (
					($errCode & TasksException::TE_ACCESS_DENIED)
					|| ($errCode & TasksException::TE_ACTION_NOT_ALLOWED)
				)
				{
					$strError .= ' (' . GetMessage('TASKS_ACTION_NOT_ALLOWED') . ')';
				}
			}
			else
				$strError .= ' (errCode #' . TasksException::renderErrorCode($e) . ')';

			if ($arResult["IS_IFRAME"])
				ShowInFrame($this, true, $strError);
			else
				ShowError($strError);

			return;
		}

		if ($redirectTo)
			LocalRedirect($redirectTo);
	}

	LocalRedirect($APPLICATION->GetCurPageParam("CALLBACK=CHANGED", array("ACTION", "sessid", "ELAPSED_ID")));
}

if ($arTask)
{
	CTasks::UpdateViewed($arTask["ID"], $loggedInUserId);

	$arResult['CHECKLIST_ITEMS'] = array();
	list($arChecklistItems, $arMetaData) = CTaskCheckListItem::fetchList($oTask, array('SORT_INDEX' => 'ASC'));
	unset($arMetaData);

	foreach ($arChecklistItems as $oChecklistItem)
	{
		$checklistItemId = $oChecklistItem->getId();
		$arResult['CHECKLIST_ITEMS'][$checklistItemId] = $oChecklistItem->getData();
		$arResult['CHECKLIST_ITEMS'][$checklistItemId]['META:CAN_MODIFY'] = $oChecklistItem->isActionAllowed(CTaskCheckListItem::ACTION_MODIFY);
		$arResult['CHECKLIST_ITEMS'][$checklistItemId]['META:CAN_REMOVE'] = $oChecklistItem->isActionAllowed(CTaskCheckListItem::ACTION_REMOVE);
		$arResult['CHECKLIST_ITEMS'][$checklistItemId]['META:CAN_TOGGLE'] = $oChecklistItem->isActionAllowed(CTaskCheckListItem::ACTION_TOGGLE);
	}

	$arTask['META:ALLOWED_ACTIONS_CODES'] = $oTask->getAllowedTaskActions();
	$arTask['META:ALLOWED_ACTIONS'] = $oTask->getAllowedTaskActionsAsStrings();

	$arTask['META:IN_DAY_PLAN'] = 'N';
	$arTask['META:CAN_ADD_TO_DAY_PLAN'] = 'N';
	if (
		(
			($arTask["RESPONSIBLE_ID"] == $loggedInUserId)
			|| (in_array($loggedInUserId, $arTask['ACCOMPLICES']))
		)
		&& CModule::IncludeModule("timeman") 
		&& (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite()))
	{
		$arTask['META:CAN_ADD_TO_DAY_PLAN'] = 'Y';

		$arTasksInPlan = CTaskPlannerMaintance::getCurrentTasksList();

		// If in day plan already
		if (
			is_array($arTasksInPlan)
			&& in_array($arTask["ID"], $arTasksInPlan)
		)
		{
			$arTask['META:IN_DAY_PLAN'] = 'Y';
			$arTask['META:CAN_ADD_TO_DAY_PLAN'] = 'N';
		}
	}

	if (!$arTask["CHANGED_DATE"])
	{
		$arTask["CHANGED_DATE"] = $arTask["CREATED_DATE"];
		$arTask["CHANGED_BY"] = $arTask["CREATED_BY"];
	}

	// Temporary fix for http://jabber.bx/view.php?id=29741
	if (strpos($arTask['DESCRIPTION'], 'player/mediaplayer/player.swf') !== false)
	{
		$arTask['~DESCRIPTION'] = str_replace(
			' src="/bitrix/components/bitrix/player/mediaplayer/player.swf" ', 
			' src="/bitrix/components/bitrix/player/mediaplayer/player" ',
			$arTask['~DESCRIPTION']
		);
		$arTask['DESCRIPTION'] = str_replace(
			' src=&quot;/bitrix/components/bitrix/player/mediaplayer/player.swf&quot; ', 
			' src=&quot;/bitrix/components/bitrix/player/mediaplayer/player&quot; ', 
			$arTask['DESCRIPTION']
		);
	}

	// group
	if ($arTask["GROUP_ID"])
	{
		$arGroup = CSocNetGroup::GetByID($arTask["GROUP_ID"]);
		$arTask["GROUP_NAME"] = $arGroup["NAME"];
	}

	if ($arTask["FILES"])
	{
		$rsFiles = CFile::GetList(array(), array("@ID" => implode(",", $arTask["FILES"])));
		$arTask["FILES"] = array();
		while($file = $rsFiles->GetNext())
		{
			$arTask["FILES"][] = $file;
		}
	}

	// comments files
	$arTask["FORUM_FILES"] = array();
	if ($arTask["FORUM_TOPIC_ID"])
	{
		$rsFiles = CForumFiles::GetList(array("ID"=>"ASC"), array("TOPIC_ID" => $arTask["FORUM_TOPIC_ID"]));
		while($arFile = $rsFiles->GetNext())
		{
			$arTask["FORUM_FILES"][] = $arFile;
		}
	}

	// templates
	$rsTemplates = CTaskTemplates::GetList(
		array("ID" => "DESC"),
		array("CREATED_BY" => $loggedInUserId, 'BASE_TEMPLATE_ID' => false, '!TPARAM_TYPE' => CTaskTemplates::TYPE_FOR_NEW_USER),
		array('NAV_PARAMS' => array('nTopCount' => 10)),
		array(),	// misc params,
		array('ID', 'TITLE', 'TASK_ID', 'REPLICATE_PARAMS')		// $arSelect
	);

	$arResult["TEMPLATES"] = array();
	while($arTemplate = $rsTemplates->GetNext())
	{
		$arResult["TEMPLATES"][] = $arTemplate;

		// try to found linked
		if (
			($arTask['FORKED_BY_TEMPLATE_ID'] > 0)
			&& ($arTemplate['ID'] == $arTask['FORKED_BY_TEMPLATE_ID'])
		)
		{
			$arLinkedTemplate = $arTemplate;
		}
		elseif (
			($arTemplate['TASK_ID'] > 0)
			&& ($arTemplate['TASK_ID'] == $arParams["TASK_ID"])
		)
		{
			$arLinkedTemplate = $arTemplate;
		}
	}

	$arLinkedTemplate = null;

	// Was task created from template?
	if ($arTask['FORKED_BY_TEMPLATE_ID'] > 0)
	{
		// Try to found this template in already fetched templates
		foreach ($arResult["TEMPLATES"] as &$arTemplate)
		{
			if ($arTemplate['ID'] == $arTask['FORKED_BY_TEMPLATE_ID'])
			{
				$arLinkedTemplate = $arTemplate;
				break;
			}
		}
		unset($arTemplate);

		// Template not found in fetched? Take it from DB
		if ($arLinkedTemplate === null)
		{
			$rsTemplate = CTaskTemplates::GetList(
				array(),
				array('ID' => $arTask['FORKED_BY_TEMPLATE_ID']),
				array(),	// nav params
				array(),	// misc params,
				array('ID', 'TASK_ID', 'REPLICATE_PARAMS')		// $arSelect
			);

			if ($arTemplate = $rsTemplate->fetch())
				$arLinkedTemplate = $arTemplate;
		}
	}
	else
	{
		// Try to found this template in already fetched templates
		foreach ($arResult['TEMPLATES'] as &$arTemplate)
		{
			if ($arTemplate['TASK_ID'] == $arParams['TASK_ID'])
			{
				$arLinkedTemplate = $arTemplate;
				break;
			}
		}
		unset($arTemplate);

		// Template not found in fetched? Take it from DB
		if ($arLinkedTemplate === null)
		{
			$rsTemplate = CTaskTemplates::GetList(
				array(),
				array('TASK_ID' => $arParams['TASK_ID']),
				array(),	// nav params
				array(),	// misc params,
				array('ID', 'TASK_ID', 'REPLICATE_PARAMS')		// $arSelect
			);

			if ($arTemplate = $rsTemplate->fetch())
				$arLinkedTemplate = $arTemplate;
		}
	}

	if ($arLinkedTemplate !== null)
	{
		if (isset($arLinkedTemplate['~REPLICATE_PARAMS']))
			$arLinkedTemplate['REPLICATE_PARAMS'] = unserialize($arLinkedTemplate['~REPLICATE_PARAMS']);
		else
			$arLinkedTemplate['REPLICATE_PARAMS'] = unserialize($arLinkedTemplate['REPLICATE_PARAMS']);

		$arTask['TEMPLATE'] = $arTask['FORKED_BY_TEMPLATE'] = $arLinkedTemplate;
	}

	$arResult["TASK"] = $arTask;

	$arTasksIDs = array($arTask['ID']);
	$arGroupsIDs = array();

	// subtasks
	$rsSubtasks = CTasks::GetList(array("GROUP_ID" => "ASC"), array("PARENT_ID" => $arParams["TASK_ID"]));
	$arResult["SUBTASKS"] = array();
	while($arSubTask = $rsSubtasks->GetNext())
	{
		$arResult["SUBTASKS"][] = $arSubTask;
		$arTasksIDs[] = $arSubTask["ID"];
		if ($arSubTask["GROUP_ID"] && !in_array($arSubTask["GROUP_ID"], $arGroupsIDs))
		{
			$arGroupsIDs[] = $arSubTask["GROUP_ID"];
		}
	}

	// previous tasks
	$rsPrevTasksIds = CTaskDependence::getList(
		array(),
		array('TASK_ID' => $arParams["TASK_ID"])
	);
	$arPrevTasksIds = array();
	while($arPrevTask = $rsPrevTasksIds->fetch())
		$arPrevTasksIds[] = (int) $arPrevTask['DEPENDS_ON_ID'];

	$arResult["PREV_TASKS"] = array();

	if ( ! empty($arPrevTasksIds) )
	{
		$rsPrevtasks = CTasks::GetList(array('GROUP_ID' => 'ASC'), array('ID' => $arPrevTasksIds));
		while($arPrevTask = $rsPrevtasks->GetNext())
		{
			$arResult["PREV_TASKS"][] = $arPrevTask;
			$arTasksIDs[] = $arPrevTask["ID"];
			if ($arPrevTask["GROUP_ID"] && !in_array($arPrevTask["GROUP_ID"], $arGroupsIDs))
			{
				$arGroupsIDs[] = $arPrevTask["GROUP_ID"];
			}
		}
	}

	$rsChildrenCount = CTasks::GetChildrenCount(array(), $arTasksIDs);
	if ($rsChildrenCount)
	{
		while($arChildrenCount = $rsChildrenCount->Fetch())
		{
			$arResult["CHILDREN_COUNT"]["PARENT_".$arChildrenCount["PARENT_ID"]] = $arChildrenCount["CNT"];
		}
	}

	// groups
	$arResult["GROUPS"] = array();
	$arOpenedProjects =  CUserOptions::GetOption("tasks", "opened_projects", array());
	if ($arResult["TASK_TYPE"] != "group" && sizeof($arGroupsIDs))
	{
		$rsGroups = CSocNetGroup::GetList(array("ID" => "ASC"), array("ID" => $arGroupsIDs));
		while($arGroup = $rsGroups->GetNext())
		{
			$arGroup["EXPANDED"] = array_key_exists($arGroup["ID"], $arOpenedProjects) && $arOpenedProjects[$arGroup["ID"]] == "false" ? false : true;
			$arResult["GROUPS"][$arGroup["ID"]] = $arGroup;
		}
	}
	// log
	$arResult["LOG"] = array();
	$rsLog = CTaskLog::GetList(
		array('CREATED_DATE' => 'DESC'),
		array("TASK_ID" => $arResult["TASK"]["ID"])
	);

	$bTzWasDisabled = ! CTimeZone::enabled();

	if ($bTzWasDisabled)
		CTimeZone::enable();

	$tzOffset = CTimeZone::getOffset();

	if ($bTzWasDisabled)
		CTimeZone::disable();

	while($arLog = $rsLog->GetNext())
	{
		// Adjust unix timestamps to "bitrix timestamps"
		if (
			isset(CTaskLog::$arComparedFields[$arLog['FIELD']]) 
			&& (CTaskLog::$arComparedFields[$arLog['FIELD']] === 'date')
		)
		{
			$arLog['~TO_VALUE']   = $arLog['TO_VALUE']   = $arLog['TO_VALUE'] + $tzOffset;
			$arLog['~FROM_VALUE'] = $arLog['FROM_VALUE'] = $arLog['FROM_VALUE'] + $tzOffset;
		}

		$arResult["LOG"][] = $arLog;
	}

	// elapsed time
	$arResult["ELAPSED_TIME"] = array();
	$arResult["FULL_ELAPSED_TIME"] = 0;
	list($oElapsedItems, $arMetaData) = CTaskElapsedItem::fetchList($oTask);
	unset($arMetaData);

	foreach ($oElapsedItems as $oElapsedItem)
	{
		$arElapsedData = $oElapsedItem->getData();
		$arElapsedData['META:CAN_MODIFY'] = $oElapsedItem->isActionAllowed(CTaskElapsedItem::ACTION_ELAPSED_TIME_MODIFY);
		$arElapsedData['META:CAN_REMOVE'] = $oElapsedItem->isActionAllowed(CTaskElapsedItem::ACTION_ELAPSED_TIME_REMOVE);
		$arResult["ELAPSED_TIME"][] = $arElapsedData;
		$arResult["FULL_ELAPSED_TIME"] += $arElapsedData['MINUTES'];
	}

	// user fields
	$arResult["USER_FIELDS"] = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("TASKS_TASK", $arParams["TASK_ID"], LANGUAGE_ID);
	$arResult["SHOW_USER_FIELDS"] = false;
	foreach($arResult["USER_FIELDS"] as $arUserField)
	{
		if ($arUserField["VALUE"] !== false)
		{
			$arResult["SHOW_USER_FIELDS"] = true;
			break;
		}
	}

	// reminders
	$arResult["REMINDERS"] = array();
	$rsReminders = CTaskReminders::GetList(array("date" => "asc"), array("USER_ID" => $loggedInUserId, "TASK_ID" => $arParams["TASK_ID"]));
	while($arReminder = $rsReminders->Fetch())
	{
		$arResult["REMINDERS"][] = array(
			"date" => $arReminder["REMIND_DATE"],
			"type" => $arReminder["TYPE"],
			"transport" => $arReminder["TRANSPORT"]
		);
	}
}
else
{
	if ($arResult["IS_IFRAME"])
		ShowInFrame($this, true, GetMessage("TASKS_TASK_NOT_FOUND"));
	else
		ShowError(GetMessage("TASKS_TASK_NOT_FOUND"));
	return;
}

$arResult['ALLOWED_ACTIONS'] = $arResult['TASK']['META:ALLOWED_ACTIONS'];

$sTitle = $arResult["TASK"]['TITLE'] . ' (' . toLower(str_replace("#TASK_NUM#", $arResult["TASK"]["ID"], GetMessage("TASKS_TASK_NUM"))) . ')';

if ($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle($sTitle);
}

if (!isset($arParams["SET_NAVCHAIN"]) || $arParams["SET_NAVCHAIN"] != "N")
{
	if ($taskType == "user")
	{
		$APPLICATION->AddChainItem(CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["USER"]), CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_USER_PROFILE"], array("user_id" => $arParams["USER_ID"])));
		$APPLICATION->AddChainItem($sTitle);
	}
	else
	{
		$APPLICATION->AddChainItem($arResult["GROUP"]["NAME"], CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_GROUP"], array("group_id" => $arParams["GROUP_ID"])));
		$APPLICATION->AddChainItem($sTitle);
	}
}

$arResult['COMPANY_WORKTIME'] = array(
	'START' => array('H' => 9, 'M' => 0, 'S' => 0),
	'END' => array('H' => 19, 'M' => 0, 'S' => 0),
);
if(CModule::IncludeModule('calendar'))
{
	$calendarSettings = CCalendar::GetSettings(array('getDefaultForEmpty' => false));

	$time = explode('.', (string) $calendarSettings['work_time_start']);
	if(intval($time[0]))
		$arResult['COMPANY_WORKTIME']['START']['H'] = intval($time[0]);
	if(intval($time[1]))
		$arResult['COMPANY_WORKTIME']['START']['M'] = intval($time[1]);

	$time = explode('.', (string) $calendarSettings['work_time_end']);
	if(intval($time[0]))
		$arResult['COMPANY_WORKTIME']['END']['H'] = intval($time[0]);
	if(intval($time[1]))
		$arResult['COMPANY_WORKTIME']['END']['M'] = intval($time[1]);
}

if ($arResult["IS_IFRAME"])
{
	ShowInFrame($this);
}
else
{
	$this->IncludeComponentTemplate();
}
