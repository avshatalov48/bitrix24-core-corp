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
	'RESPONSIBLE_ID' => array('id' => 'RESPONSIBLE_ID', 'name' => GetMessage('TASK_COLUMN_RESPONSIBLE_ID'), 'class' => 'username', 'type' => ''),
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
	$task["STATUS"] = GetMessage("TASKS_STATUS_" . $arResult["STATUSES"][$task["REAL_STATUS"]]);
	$task["STATUS"] = '<span id="bx-task-status-'.$task["ID"].'">' . ( empty($task["STATUS"]) ? GetMessage("TASKS_STATUS_STATE_UNKNOWN") : $task["STATUS"]) . "</span>";
	if (!empty($task["DEADLINE"]))
		$task["DEADLINE"] = '<span id="bx-task-deadline-'.$task["ID"].'">'.trim(FormatDate($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($task["DEADLINE"])), ", .").'</span>';
	if ($task["~STATUS"] == CTasks::METASTATE_EXPIRED)
		$task["EXPIRED"] = '<span class="mobile-grid-field-expired">'.GetMessage("TASK_EXPIRED").'</span>';

	$users[] = $task["CREATED_BY"];
	$users[] = $task["RESPONSIBLE_ID"];

	$task["PRIORITY"] = ($task["~PRIORITY"] == CTasks::PRIORITY_HIGH ?
		"<label class=\"mobile-grid-field-priority mobile-grid-field-priority-2\"><span>".GetMessage("TASKS_PRIORITY_2")."</span></label>" : "");

	$arResult["ITEMS"][$task['ID']] = array(
		"~TITLE" => $task["~TITLE"],
		"ICON_HTML" => '<span id="bx-task-icon-'.$task["ID"].'" class="mobile-grid-fields-task-icon '.strtolower($arResult["STATUSES"][$task["~STATUS"]]).'"></span>',
		"TITLE" => '<span class="mobile-grid-fields-task-title" id="bx-task-title-'.$task["ID"].'">'.$task["TITLE"].'</span>',
		"FIELDS" => $task,
		"ONCLICK" => "BXMobileApp.onCustomEvent('onTaskWasChosenInTasksSelector', [".
					 intval($_GET["id"]).
					 ", ".
					 CUtil::PhpToJSObject(array("ID" => $task["ID"], "TITLE" => $task["TITLE"])).
					 "], true, true);",
		"DATA_ID" => "mobile-grid-item-".$task["ID"]
	);
}
if (!empty($users))
{
	$dbRes = CUser::GetList(($by="ID"), ($order="ASC"), array("@ID" => $users), array("SELECT" => array("ID", "NAME", "SECOND_NAME", "LAST_NAME", "PERSONAL_PHOTO")));
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