<?

use Bitrix\Tasks\Internals\Task\MetaStatus;
use Bitrix\Tasks\Internals\Task\Priority;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * @var CMain $APPLICATION
 * @var CDatabase $DB
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponent $component
 * @var CUser $USER
 */
if (empty($arParams["DATE_TIME_FORMAT"]) ||  $arParams["DATE_TIME_FORMAT"] === "FULL")
{
	$arParams["DATE_TIME_FORMAT"]= $DB->DateFormatToPHP(FORMAT_DATETIME);
}
$arParams["DATE_TIME_FORMAT"] = preg_replace('/[\/.,\s:][s]/', '', $arParams["DATE_TIME_FORMAT"]);
if (
	!isset($arParams["TIME_FORMAT"])
	|| !$arParams["TIME_FORMAT"]
)
{
	$arParams["TIME_FORMAT"] = preg_replace(
		['/[dDjlFmMnYyo]/', '/^[\/.,\s]+/', '/[\/.,\s]+$/'],
		'',
		$arParams["DATE_TIME_FORMAT"]
	);
}

$arParams["DATE_TIME_FORMAT"] = array(
	"tomorrow" => "tomorrow, ".$arParams["TIME_FORMAT"],
	"today" => "today, ".$arParams["TIME_FORMAT"],
	"yesterday" => "yesterday, ".$arParams["TIME_FORMAT"],
	"" => $arParams["DATE_TIME_FORMAT"]
);
$arParams['GRID_ID'] = 'mobile_tasks_list_selector';
$gridOptions = CUserOptions::GetOption("mobile.interface.grid", $arParams["GRID_ID"]);
$fields = array("STATUS", "DEADLINE", "RESPONSIBLE_ID", "CREATED_BY", "PRIORITY");
if (is_array($gridOptions) && is_array($gridOptions["fields"]))
	$fields = $gridOptions["fields"];
if (in_array("DEADLINE", $fields))
{
	$key = array_search("DEADLINE", $fields);
	array_splice($fields, ++$key, 0, "EXPIRED");
}
$supportedFields = array(
	'ID' => array('id' => 'ID', 'name' => "ID", 'class' => '', 'type' => ''),
	'STATUS' => array('id' => 'STATUS', 'name' => GetMessage('TASK_COLUMN_STATUS'), 'class' => '', 'type' => ''),
	'DEADLINE' => array('id' => 'DEADLINE', 'name' => GetMessage('TASK_COLUMN_DEADLINE'), 'class' => 'date', 'type' => 'date'),
	'EXPIRED' => array('id' => 'EXPIRED', 'name' => "", 'class' => '', 'type' => ''),
	'CREATED_BY' => array('id' => 'CREATED_BY', 'name' => GetMessage('TASK_COLUMN_CREATED_BY'), 'class' => 'username'),
	'RESPONSIBLE_ID' => array('id' => 'RESPONSIBLE_ID', 'name' => GetMessage('TASK_COLUMN_RESPONSIBLE_ID_MSGVER_1'), 'class' => 'username', 'type' => ''),
	'PRIORITY' => array('id' => 'PRIORITY', 'name' => GetMessage('TASK_COLUMN_PRIORITY'), 'class' => '', 'type' => ''),
);
$arResult["FIELDS"] = array();
foreach($fields as $key)
{
	if (array_key_exists($key, $supportedFields))
		$arResult["FIELDS"][$key] = $supportedFields[$key];
}
$arResult["ITEMS"] = array();
$arResult["STATUSES"] = CTaskItem::getStatusMap();
$users = array();
foreach ($arResult["LAST_TASKS"] as $task)
{
	$taskId = $task['ID'];
	$taskTitle = htmlspecialcharsbx($task['TITLE']);

	$status = (isset($task['REAL_STATUS']) ? $arResult['STATUSES'][$task['REAL_STATUS']] : '');
	$task["STATUS"] = GetMessage("TASKS_STATUS_{$status}");
	$task["STATUS"] = '<span id="bx-task-status-' . $taskId . '">' . ( empty($task["STATUS"]) ? GetMessage("TASKS_STATUS_STATE_UNKNOWN") : $task["STATUS"]) . "</span>";

	if (!empty($task["DEADLINE"]))
	{
		$task["DEADLINE"] = '<span id="bx-task-deadline-'.$taskId.'">'.trim(FormatDate($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($task["DEADLINE"])), ", .").'</span>';
	}
	if (isset($task['~STATUS']) && (int)$task["~STATUS"] === MetaStatus::EXPIRED)
	{
		$task["EXPIRED"] = '<span class="mobile-grid-field-expired">'.GetMessage("TASK_EXPIRED").'</span>';
	}

	if (array_key_exists('CREATED_BY', $task))
	{
		$users[] = $task["CREATED_BY"];
	}
	if (array_key_exists('RESPONSIBLE_ID', $task))
	{
		$users[] = $task["RESPONSIBLE_ID"];
	}

	$task["PRIORITY"] = (
		isset($task["~PRIORITY"]) && (int)$task["~PRIORITY"] === Priority::HIGH
			? "<label class=\"mobile-grid-field-priority mobile-grid-field-priority-2\"><span>" . GetMessage("TASKS_PRIORITY_2") . "</span></label>"
			: ""
	);

	$arResult["ITEMS"][$taskId] = [
		"~TITLE" => ($task["~TITLE"] ?? null),
		"ICON_HTML" => '<span id="bx-task-icon-'.$taskId.'" class="mobile-grid-fields-task-icon '.$status.'"></span>',
		"TITLE" => '<span class="mobile-grid-fields-task-title" id="bx-task-title-'.$taskId.'">'.$taskTitle.'</span>',
		"FIELDS" => $task,
		"ONCLICK" => "BXMobileApp.onCustomEvent('onTaskWasChosenInTasksSelector', ["
			.(int)$_GET["id"].", ".CUtil::PhpToJSObject(["ID" => $taskId, "TITLE" => $taskTitle])
		."], true, true);",
		"DATA_ID" => "mobile-grid-item-{$taskId}",
	];
}
if (!empty($users))
{
	$dbRes = CUser::GetList("ID", "ASC", array("@ID" => $users), array("SELECT" => array("ID", "NAME", "SECOND_NAME", "LAST_NAME", "PERSONAL_PHOTO")));
	$users = array();
	while (($user=$dbRes->fetch()) && $user)
		$users[$user["ID"]] = tasksFormatNameShort($user["NAME"], $user["LAST_NAME"], $user["LOGIN"], $user["SECOND_NAME"], $arParams["NAME_TEMPLATE"], true);
	foreach ($arResult["ITEMS"] as &$taskField)
	{
		$task = &$taskField["FIELDS"];
		if (array_key_exists($task["CREATED_BY"], $users))
		{
			$task["CREATED_BY"] = '<span id="bx-task-created_by-'.$task["ID"].'" class="mobile-grid-field-link" '.
				'bx-user_id="'. $task["~CREATED_BY"] .'" onclick="BX.Mobile.Tasks.go(this)">'.$users[$task["CREATED_BY"]].'</span>';
		}
		if (array_key_exists($task["RESPONSIBLE_ID"], $users))
		{
			$task["RESPONSIBLE_ID"] = '<span id="bx-task-responsible_id-'.$task["ID"].'" class="mobile-grid-field-link" '.
				'bx-user_id="'. $task["~RESPONSIBLE_ID"] .'" onclick="BX.Mobile.Tasks.go(this)">'.$users[$task["RESPONSIBLE_ID"]].'</span>';
		}
	}
}