<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * @var CMain $APPLICATION
 * @var CDatabase $DB
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponent $component
 * @var CUser $USER
 */
if (empty($arParams["DATE_TIME_FORMAT"]) ||  $arParams["DATE_TIME_FORMAT"] == "FULL")
	$arParams["DATE_TIME_FORMAT"]= $DB->DateFormatToPHP(FORMAT_DATETIME);
$arParams["DATE_TIME_FORMAT"] = preg_replace('/[\/.,\s:][s]/', '', $arParams["DATE_TIME_FORMAT"]);
if (!$arParams["TIME_FORMAT"])
	$arParams["TIME_FORMAT"] = preg_replace(array('/[dDjlFmMnYyo]/', '/^[\/.,\s]+/', '/[\/.,\s]+$/'), "", $arParams["DATE_TIME_FORMAT"]);

$arParams["DATE_TIME_FORMAT"] = array(
	"tomorrow" => "tomorrow, ".$arParams["TIME_FORMAT"],
	"today" => "today, ".$arParams["TIME_FORMAT"],
	"yesterday" => "yesterday, ".$arParams["TIME_FORMAT"],
	"" => $arParams["DATE_TIME_FORMAT"]
);

$arParams['GRID_ID'] = 'mobile_tasks_list_'.$arResult['COLUMNS_CONTEXT_ID'];
$gridOptions = CUserOptions::GetOption("mobile.interface.grid", $arParams["GRID_ID"]);
$fields = array("STATUS", "DEADLINE", "RESPONSIBLE_ID", "PRIORITY", "FAVORITES");
if (is_array($gridOptions) && is_array($gridOptions["fields"]))
	$fields = $gridOptions["fields"];
else if ($arResult['VIEW_STATE']['ROLE_SELECTED']['ID'] == CTaskListState::VIEW_ROLE_RESPONSIBLE ||
	$arResult['VIEW_STATE']['ROLE_SELECTED']['ID'] == CTaskColumnContext::CONTEXT_ACCOMPLICE)
	$fields = array("STATUS", "DEADLINE", "CREATED_BY", "PRIORITY", "FAVORITES");
if (in_array("DEADLINE", $fields))
{
	$key = array_search("DEADLINE", $fields);
	array_splice($fields, ++$key, 0, "EXPIRED");
	array_splice($fields, ++$key, 0, "TIMETRACKING");
}
else
{
	array_unshift($fields, "TIMETRACKING");
}
$supportedFields = array(
	'ID' => array('id' => 'ID', 'name' => "ID", 'class' => '', 'type' => ''),
	'STATUS' => array('id' => 'STATUS', 'name' => GetMessage('TASK_COLUMN_STATUS'), 'class' => '', 'type' => ''),
	'DEADLINE' => array('id' => 'DEADLINE', 'name' => GetMessage('TASK_COLUMN_DEADLINE'), 'class' => 'date', 'type' => 'date'),
	'EXPIRED' => array('id' => 'EXPIRED', 'name' => "", 'class' => '', 'type' => ''),
	'TIMETRACKING' => array('id' => 'TIMETRACKING', 'name' => GetMessage("TASK_COLUMN_TIMETRACKING"), 'class' => '', 'type' => ''),
	'CREATED_BY' => array('id' => 'CREATED_BY', 'name' => GetMessage('TASK_COLUMN_CREATED_BY'), 'class' => 'username'),
	'RESPONSIBLE_ID' => array('id' => 'RESPONSIBLE_ID', 'name' => GetMessage('TASK_COLUMN_RESPONSIBLE_ID'), 'class' => 'username', 'type' => ''),
	'PRIORITY' => array('id' => 'PRIORITY', 'name' => GetMessage('TASK_COLUMN_PRIORITY'), 'class' => '', 'type' => ''),
	'MARK' => array('id' => 'MARK', 'name' => GetMessage('TASK_COLUMN_MARK'), 'class' => '', 'type' => ''),
	'GROUP_ID' => array('id' => 'GROUP_ID', 'name' => GetMessage('TASK_COLUMN_GROUP_ID'), 'class' => '', 'type' => 'date'),
	'TIME_ESTIMATE' => array('id' => 'TIME_ESTIMATE', 'name' => GetMessage('TASK_COLUMN_TIME_ESTIMATE'), 'class' => '', 'type' => 'date'),
	'ALLOW_TIME_TRACKING' => array('id' => 'ALLOW_TIME_TRACKING', 'name' => GetMessage('TASK_COLUMN_ALLOW_TIME_TRACKING'), 'class' => 'date', 'type' => 'date'),
	'TIME_SPENT_IN_LOGS' => array('id' => 'TIME_SPENT_IN_LOGS', 'name' => GetMessage('TASK_COLUMN_TIME_SPENT_IN_LOGS'), 'class' => '', 'type' => 'date'),
	'ALLOW_CHANGE_DEADLINE' => array('id' => 'ALLOW_CHANGE_DEADLINE', 'name' => GetMessage('TASK_COLUMN_ALLOW_CHANGE_DEADLINE'), 'class' => '', 'type' => 'date'),
	'CREATED_DATE' => array('id' => 'CREATED_DATE', 'name' => GetMessage('TASK_COLUMN_CREATED_DATE'), 'class' => '', 'type' => 'date'),
	'CHANGED_DATE' => array('id' => 'CHANGED_DATE', 'name' => GetMessage('TASK_COLUMN_CHANGED_DATE'), 'class' => '', 'type' => 'date'),
	//'UF_CRM_TASK' => array('id' => 'CLOSED_DATE', 'name' => "CRM", 'class' => '', 'type' => 'date'),
	'CLOSED_DATE' => array('id' => 'CLOSED_DATE', 'name' => GetMessage('TASK_COLUMN_CLOSED_DATE'), 'class' => '', 'type' => 'date'),
	'FAVORITES' => array('id' => 'FAVORITES', 'name' => GetMessage('TASK_COLUMN_FAVORITES'), 'class' => '', 'type' => ''),
);
$arResult["FIELDS"] = array();
foreach($fields as $key)
{
	if (array_key_exists($key, $supportedFields))
		$arResult["FIELDS"][$key] = $supportedFields[$key];
}
$arResult["ITEMS"] = array();
$arResult["ITEMSJS"] = array();
$arResult["STATUSES"] = CTaskItem::getStatusMap();

foreach ($arResult["TASKS"] as $task)
{
	$arActions = array(
		array(
			"ID" => "bx-task-start-".$task["ID"],
			"TEXT" => GetMessage("TASKS_LIST_GROUP_ACTION_START"),
			"ONCLICK" => "BX.Mobile.Tasks.list.act('start', ".$task["ID"].")",
			"HIDDEN" => !$task["META:ALLOWED_ACTIONS"]["ACTION_START"]
		),
		array(
			"ID" => "bx-task-complete-".$task["ID"],
			"TEXT" => GetMessage("TASKS_LIST_GROUP_ACTION_COMPLETE"),
			"ONCLICK" => "BX.Mobile.Tasks.list.act('complete', ".$task["ID"].")",
			"HIDDEN" => !$task["META:ALLOWED_ACTIONS"]["ACTION_COMPLETE"]
		)
	);
	$arActions[] = array(
		"TEXT" => GetMessage("TASKS_LIST_COLUMN_18"),
		'ONCLICK' => "BX.Mobile.Tasks.list.actShow(".$task["ID"].")",
		'DISABLE' => false
	);

	if (!empty($task["DESCRIPTION"]))
		$task["DESCRIPTION"] = '<span id="bx-task-description-'.$task["ID"].'">' . $task["DESCRIPTION"] . '</span>';

	$task["STATUS"] = GetMessage("TASKS_STATUS_" . $arResult["STATUSES"][$task["REAL_STATUS"]]);
	$task["STATUS"] = '<span id="bx-task-status-'.$task["ID"].'">' . ( empty($task["STATUS"]) ? GetMessage("TASKS_STATUS_STATE_UNKNOWN") : $task["STATUS"]) . "</span>";
	if (!empty($task["DEADLINE"]))
		$task["DEADLINE"] = '<span id="bx-task-deadline-'.$task["ID"].'">'.trim(FormatDate($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($task["DEADLINE"])), ", .").'</span>';
	if ($task["~STATUS"] == CTasks::METASTATE_EXPIRED)
		$task["EXPIRED"] = '<span class="mobile-grid-field-expired">'.GetMessage("TASK_EXPIRED").'</span>';

	$user = (CUser::GetByID($task["CREATED_BY"])->fetch() ?: array());
	$task["CREATED_BY"] = tasksFormatNameShort($user["NAME"], $user["LAST_NAME"], $user["LOGIN"], $user["SECOND_NAME"], $arParams["NAME_TEMPLATE"], true);
	$task["CREATED_BY"] = '<span id="bx-task-created_by-'.$task["ID"].'" class="mobile-grid-field-link" '.
		'bx-user_id="'. $task["~CREATED_BY"] .'" '.
		'onclick="BX.Mobile.Tasks.go(this)">'.$task["CREATED_BY"].'</span>';

	$user = (CUser::GetByID($task["RESPONSIBLE_ID"])->fetch() ?: array());
	$task["RESPONSIBLE_ID"] = tasksFormatNameShort($user["NAME"], $user["LAST_NAME"], $user["LOGIN"], $user["SECOND_NAME"], $arParams["NAME_TEMPLATE"], true);
	$task["RESPONSIBLE_ID"] = '<span id="bx-task-responsible_id-'.$task["ID"].'" class="mobile-grid-field-link" '.
		'bx-user_id="'. $task["~RESPONSIBLE_ID"] .'" '.
		'onclick="BX.Mobile.Tasks.go(this)">'.$task["RESPONSIBLE_ID"].'</span>';

	$task["PRIORITY"] = ($task["~PRIORITY"] == CTasks::PRIORITY_HIGH ?
		"<label class=\"mobile-grid-field-priority mobile-grid-field-priority-2\"><span>".GetMessage("TASKS_PRIORITY_2")."</span></label>" : "");
	if ($task['META:ALLOWED_ACTIONS']['ACTION_EDIT'])
	{
		$task["PRIORITY"] = "<label for=\"bx-task-priority-".$task["ID"]."\" class=\"mobile-grid-field-priority\">".
				"<input type=\"checkbox\" id=\"bx-task-priority-".$task["ID"]."\" value=\"2\" ".($task["~PRIORITY"] == CTasks::PRIORITY_HIGH ? "checked" : "")." class=\"mobile-grid-field-priority\">".
				"<span>".GetMessage("TASKS_PRIORITY_0")."</span>".
				"<span>".GetMessage("TASKS_PRIORITY_2")."</span>".
			"</label>";
	}
	$task["MARK"] = '<span class="bx-tasks-task-mark bx-tasks-task-mark-'.$task["MARK"].'" id="bx-task-mark-'.$task["ID"].'">' . GetMessage("TASKS_MARK_".$task["MARK"])."</span>";

	if ($task["GROUP_ID"] > 0)
		$task["GROUP_ID"] = $arResult["GROUPS"][$task["GROUP_ID"]]["NAME"];

	$task["TIMETRACKING"] = "";
	$task["TIME"] = "";
	if ($task["ALLOW_TIME_TRACKING"] == "Y")
	{
		$task["ALLOW_TIME_TRACKING"] = GetMessage("TASK_ALLOWED");
		$timerTask = CTaskTimerManager::getInstance($USER->getId())->getRunningTask(false);
		$timerTask = is_array($timerTask) ? $timerTask : array();
		$task["TIME_SPENT_IN_LOGS"] += ($timerTask['TASK_ID'] == $task['ID'] ? $timerTask['RUN_TIME'] : 0);
		$task["TIMETRACKING"] =
		"<label for=\"bx-task-timetracking-".$task["ID"]."\" class=\"mobile-grid-field-timetracking mobile-grid-field-timetracking-act\">".
			"<input type=\"checkbox\" id=\"bx-task-timetracking-".$task["ID"]."\" value=\"".intval($task["TIME_SPENT_IN_LOGS"])."\" ".
				($timerTask['TASK_ID'] == $task['ID'] ? " checked" : "").(
				($timerTask['TASK_ID'] == $task['ID'] || $task["META:ALLOWED_ACTIONS"]["ACTION_START_TIME_TRACKING"]) ? "" : " disabled")." />".
			"<span class=\"start\">".($task["TIME_SPENT_IN_LOGS"] > 0 ? GetMessage("TASKS_TT_CONTINUE") : GetMessage("TASKS_TT_START"))."</span>".
			"<span class=\"pause\">".GetMessage("TASKS_TT_PAUSE")."</span>".
			"<span class=\"timetracking\"><span class=\"spent\"  id=\"bx-task-timetracking-".$task["ID"]."-value\">".sprintf('%02d:%02d:%02d', floor($task['TIME_SPENT_IN_LOGS']  / 3600), floor($task['TIME_SPENT_IN_LOGS'] / 60) % 60, $task['TIME_SPENT_IN_LOGS'] % 60)."</span>".
		(
			$task["TIME_ESTIMATE"] > 0 ?
				"<span class=\"divider\"> / </span><span class=\"estimated\">". sprintf('%02d:%02d', floor($task['TIME_ESTIMATE']  / 3600), floor($task['TIME_ESTIMATE'] / 60) % 60). "</span>" :
				""
		).
		"</span></label>";
	}
	else
	{
		$task["ALLOW_TIME_TRACKING"] = GetMessage("TASK_DISALLOWED");
	}
	if ($task["TIME_ESTIMATE"] > 0)
	{
		$task["~TIME_ESTIMATE"] = $task["TIME_ESTIMATE"];
		$task["TIME_ESTIMATE"] = sprintf('%02d:%02d', floor($task['TIME_ESTIMATE']  / 3600), floor($task['TIME_ESTIMATE'] / 60) % 60);
	}

	if ($task["TIME_SPENT_IN_LOGS"] > 0)
	{
		$task["TIME_SPENT_IN_LOGS"] = sprintf(
			'%02d:%02d:%02d',
			floor($task["TIME_SPENT_IN_LOGS"] / 3600),		// hours
			floor($task["TIME_SPENT_IN_LOGS"] / 60) % 60,	// minutes
			$task["TIME_SPENT_IN_LOGS"] % 60					// seconds
		);
	}
	$task["ALLOW_CHANGE_DEADLINE"] = ($task["ALLOW_CHANGE_DEADLINE"] == "Y" ? GetMessage("TASK_ALLOWED") : GetMessage("TASK_DISALLOWED"));

	if (!empty($task["CREATED_DATE"]))
		$task["CREATED_DATE"] = '<span id="bx-task-created_date-'.$task["ID"].'">'.trim(FormatDate($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($task["CREATED_DATE"])), ", .").'</span>';
	if (!empty($task["CHANGED_DATE"]))
		$task["CHANGED_DATE"] = '<span id="bx-task-changed_date-'.$task["ID"].'">'.trim(FormatDate($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($task["CHANGED_DATE"])), ", .").'</span>';
	if (!empty($task["CLOSED_DATE"]))
		$task["CLOSED_DATE"] = '<span id="bx-task-closed_date-'.$task["ID"].'">'.trim(FormatDate($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($task["CLOSED_DATE"])), ", .").'</span>';
	$task["FAVORITES"] = "";
	if ($task["META:ALLOWED_ACTIONS"]["ACTION_ADD_FAVORITE"] || $task["META:ALLOWED_ACTIONS"]["ACTION_DELETE_FAVORITE"])
		$task["FAVORITES"] =
		'<label for="bx-task-favorites-'.$task["ID"].'" class="mobile-grid-field-favorites">'.
			'<input id="bx-task-favorites-'.$task["ID"].'" type="checkbox"'.($task["META:ALLOWED_ACTIONS"]["ACTION_DELETE_FAVORITE"] ? " checked=\"checked\"" : "") .' />'.
			'<span>'.GetMessage("TASKS_FAVORITES_0").'</span>'.
			'<span>'.GetMessage("TASKS_FAVORITES_1").'</span>'.
		'</label>';

	$jsTask = array(
		"ID" => $task["ID"],
		"TITLE" => htmlspecialcharsbx($task['~TITLE']),
		"DESCRIPTION" => htmlspecialcharsbx($task['DESCRIPTION']),
		"RESPONSIBLE_ID" => intval($task['~RESPONSIBLE_ID']),
		"CREATED_BY" => intval($task['~CREATED_BY']),
		"PRIORITY" => $task['~PRIORITY'],
		"STATUS" => $task['~STATUS'],
		"REAL_STATUS" => $task['~REAL_STATUS'],
		"DEADLINE" => $task['~DEADLINE'],
		"TIME_SPENT_IN_LOGS" => $task["TIME_SPENT_IN_LOGS"],
		"TIME_ESTIMATE" => $task["~TIME_ESTIMATE"],
		"ALLOWED_ACTIONS" => $task['META:ALLOWED_ACTIONS']
	);
	$arResult["ITEMS"][$task['ID']] = array(
		"~TITLE" => $task["~TITLE"],
		"ICON_HTML" => '<span id="bx-task-icon-'.$task["ID"].'" class="mobile-grid-fields-task-icon '.strtolower($arResult["STATUSES"][$task["~STATUS"]]).'"></span>',
		"TITLE" => '<span class="mobile-grid-fields-task-title" id="bx-task-title-'.$task["ID"].'">'.$task["TITLE"].'</span>'.
			(isset($_POST["ajax"]) && $_POST["ajax"] == "Y" || isset($_REQUEST["search"]) && $arParams["SHOW_SEARCH"] == "Y" ?
				'<script>BX.Mobile.Tasks.list.addCurrent('.$task["ID"].', '.(CUtil::PhpToJSObject($jsTask)).');</script>' : ''),
		"ACTIONS" => $arActions,
		"FIELDS" => $task,
		"ONCLICK" => "BXMobileApp.PageManager.loadPageUnique({url:'".CComponentEngine::MakePathFromTemplate(
				$arParams["~PATH_TO_USER_TASKS_TASK"],
				array("USER_ID" => $arParams["USER_ID"], "TASK_ID" => $task["ID"]))."', bx24ModernStyle: true});",
		"DATA_ID" => "mobile-grid-item-".$task["ID"]
	);

	$arResult["ITEMSJS"][] = $jsTask;
}