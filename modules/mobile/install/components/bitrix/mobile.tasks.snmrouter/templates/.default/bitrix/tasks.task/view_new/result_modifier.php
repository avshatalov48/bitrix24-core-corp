<?
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var TasksBaseComponent $component */

$arResult["TEMPLATE_DATA"] = array();
if (is_array($arResult["ERROR"]) && !empty($arResult["ERROR"]))
{
	foreach($arResult["ERROR"] as $error)
	{
		if ($error["TYPE"] == "FATAL")
		{
			$arResult["TEMPLATE_DATA"]["ERROR"] = $error;
			return;
		}
	}
}

$taskData = $arResult["DATA"]["TASK"];
if (empty($taskData) || !isset($taskData["ID"]))
{
	$arResult["TEMPLATE_DATA"]["ERROR"] = array(
		"TYPE" => "FATAL",
		"MESSAGE" => Loc::getMessage("TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE")
	);

	return;
}
$component = $this->__component;


//User Name Template
$arParams["NAME_TEMPLATE"] = empty($arParams["NAME_TEMPLATE"]) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arResult["TEMPLATE_DATA"]["NAME_TEMPLATE"] = $arParams["NAME_TEMPLATE"];

//Do we have to show user fields?
$arResult["TEMPLATE_DATA"]["SHOW_USER_FIELDS"] = false;
$userFields = isset($arResult["AUX_DATA"]["USER_FIELDS"]) ? $arResult["AUX_DATA"]["USER_FIELDS"] : array();
foreach($userFields as $fieldId => $field)
{
	if ($field["VALUE"] !== false && !empty($field["VALUE"]) && $fieldId !== "UF_TASK_WEBDAV_FILES")
	{
		$arResult["TEMPLATE_DATA"]["SHOW_USER_FIELDS"] = true;
		break;
	}
}

$taskType = $arParams["GROUP_ID"] > 0 ? "group" : "user";
$arResult["TEMPLATE_DATA"]["TASK_TYPE"] = $taskType;

if ($taskType == "user")
{
	$arParams["PATH_TO_TASKS"] = str_replace(array("#user_id#", "#USER_ID#"), $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace(array("#user_id#", "#USER_ID#"), $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]);
}
else
{
	$arParams["PATH_TO_TASKS"] = str_replace(array("#group_id#", "#GROUP_ID#"), $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace(array("#group_id#", "#GROUP_ID#"), $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS_TASK"]);
}

if(is_array($arParams['TASK_URL_PARAMETERS']) && !empty($arParams['TASK_URL_PARAMETERS']))
{
	if((string) $arParams['PATH_TO_TASKS_TASK'] != '')
	{
		$arParams['PATH_TO_TASKS_TASK'] = \Bitrix\Tasks\Util::replaceUrlParameters($arParams['PATH_TO_TASKS_TASK'], $arParams['TASK_URL_PARAMETERS']);
	}
}

// Template Paths
$arParams["PATH_TO_TEMPLATES_TEMPLATE"] = str_replace(array("#user_id#", "#USER_ID#"), $arParams["USER_ID"], $arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]);
$arParams["PATH_TO_TASKS_TEMPLATES"] = str_replace(array("#user_id#", "#USER_ID#"),$arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TEMPLATES"]);

//New Task Path
$arResult["TEMPLATE_DATA"]["NEW_TASK_PATH"] = CComponentEngine::makePathFromTemplate(
	$arParams["PATH_TO_TASKS_TASK"],
	array("task_id" => 0, "action" => "edit")
);

$arResult["TEMPLATE_DATA"]["NEW_SUBTASK_PATH"] =
	$arResult["TEMPLATE_DATA"]["NEW_TASK_PATH"].
	(mb_strpos($arResult["TEMPLATE_DATA"]["NEW_TASK_PATH"], "?") === false ? "?" : "&").
	"PARENT_ID=".$taskData["ID"];

//Rating
$arResult["TEMPLATE_DATA"]["RATING"] = CRatings::GetRatingVoteResult("TASK", $taskData["ID"]);

//Group
$arResult["TEMPLATE_DATA"]["GROUP_URL_TEMPLATE"] = CComponentEngine::makePathFromTemplate(
	$arParams["PATH_TO_GROUP"],
	array("group_id" => "{{VALUE}}")
);

$arResult["TEMPLATE_DATA"]["GROUP"] = array();
if ($taskData["GROUP_ID"] &&
	isset($arResult["DATA"]["GROUP"][$taskData["GROUP_ID"]]) &&
	CSocNetGroup::CanUserViewGroup($USER->getID(), $taskData["GROUP_ID"]))
{
	$group = $arResult["DATA"]["GROUP"][$taskData["GROUP_ID"]];
	$arResult["TEMPLATE_DATA"]["GROUP"]["NAME"] = $group["NAME"];
	$arResult["TEMPLATE_DATA"]["GROUP"]["ID"] = $group["ID"];
	$arResult["TEMPLATE_DATA"]["GROUP"]["URL"] = CComponentEngine::makePathFromTemplate(
		$arParams["PATH_TO_GROUP"],
		array("group_id" => $taskData["GROUP_ID"])
	);
}

//Parent Task
$arResult["TEMPLATE_DATA"]["RELATED_TASK"] = array();
if (isset($arResult["DATA"]["RELATED_TASK"]) && isset($arResult["DATA"]["RELATED_TASK"][$taskData["PARENT_ID"]]))
{
	$arResult["TEMPLATE_DATA"]["RELATED_TASK"] = $arResult["DATA"]["RELATED_TASK"][$taskData["PARENT_ID"]];
	$arResult["TEMPLATE_DATA"]["RELATED_TASK"]["URL"] = CComponentEngine::makePathFromTemplate(
		$arParams["PATH_TO_TASKS_TASK"],
		array(
			"task_id" => $taskData["PARENT_ID"],
			"action" => "view")
	);
}
//SubTasks
$subtasks = CTasks::GetList(array("GROUP_ID" => "ASC"), array("PARENT_ID" => $taskData["ID"]), array(), array("nPageTop" => 1));
$arResult["TEMPLATE_DATA"]["SUBTASKS_EXIST"] = $subtasks->fetch() !== false;

//Predecessors
$arResult["TEMPLATE_DATA"]["PREDECESSORS"] = array();
foreach ($taskData["SE_PROJECTDEPENDENCE"] as $dependency)
{
	$depTaskId = $dependency["SE_DEPENDS_ON"]["ID"];
	if (isset($arResult["DATA"]["RELATED_TASK"]) && isset($arResult["DATA"]["RELATED_TASK"][$depTaskId]))
	{
		$depTask = $arResult["DATA"]["RELATED_TASK"][$depTaskId];
		$depTask["TASK_URL"] = CComponentEngine::makePathFromTemplate(
			$arParams["PATH_TO_TASKS_TASK"],
			array("task_id" => $depTaskId, "action" => "view")
		);

		$type = intval($dependency["TYPE"]);
		if ($type === 0)
		{
			$depTask["DEPENDENCY_TYPE"] = Loc::getMessage("TASKS_DEPENDENCY_START")."-".Loc::getMessage("TASKS_DEPENDENCY_START");
		}
		else if ($type === 1)
		{
			$depTask["DEPENDENCY_TYPE"] = Loc::getMessage("TASKS_DEPENDENCY_START")."-".Loc::getMessage("TASKS_DEPENDENCY_END");
		}
		else if ($type === 2)
		{
			$depTask["DEPENDENCY_TYPE"] = Loc::getMessage("TASKS_DEPENDENCY_END")."-".Loc::getMessage("TASKS_DEPENDENCY_START");
		}
		else if ($type === 3)
		{
			$depTask["DEPENDENCY_TYPE"] = Loc::getMessage("TASKS_DEPENDENCY_END")."-".Loc::getMessage("TASKS_DEPENDENCY_END");
		}

		$arResult["TEMPLATE_DATA"]["PREDECESSORS"][] = $depTask;
	}
}

//Previous Tasks
$arResult["TEMPLATE_DATA"]["PREV_TASKS"] = array();
$prevTasks = CTaskDependence::getList(array(), array("TASK_ID" => $taskData["ID"]));
$prevTaskIds = array();
while ($item = $prevTasks->fetch())
{
	$prevTaskIds[] = (int) $item['DEPENDS_ON_ID'];
}

if (!empty($prevTaskIds))
{
	$prevTasks = CTasks::GetList(array("GROUP_ID" => "ASC"), array("ID" => $prevTaskIds));
	while($item = $prevTasks->fetch())
	{
		$item["RESPONSIBLE_URL"] = CComponentEngine::makePathFromTemplate(
			$arParams["~PATH_TO_USER_PROFILE"],
			array("user_id" => $item["RESPONSIBLE_ID"])
		);

		$item["TASK_URL"] = CComponentEngine::makePathFromTemplate(
			$arParams["PATH_TO_TASKS_TASK"],
			array("task_id" => $item["ID"], "action" => "view")
		);

		$item["RESPONSIBLE_FORMATTED_NAME"] = CUser::FormatName($arParams["NAME_TEMPLATE"], array(
			"NAME" => $item["RESPONSIBLE_NAME"],
			"LAST_NAME" => $item["RESPONSIBLE_LAST_NAME"],
			"SECOND_NAME" => $item["RESPONSIBLE_SECOND_NAME"],
			"LOGIN" => $item["RESPONSIBLE_LOGIN"]
		), true, false);

		$arResult["TEMPLATE_DATA"]["PREV_TASKS"][] = $item;
	}
}

//Timer
//$timerData = CTaskTimerManager::getInstance($USER->getId())->getLastTimer(false);
//$arResult["TEMPLATE_DATA"]["TIMER"] = $timerData;

$arResult["TEMPLATE_DATA"]["TIMER_IS_RUNNING_FOR_CURRENT_USER"] = $taskData['TIMER_IS_RUNNING'] ? "Y" : "N";

//Files in Comments
$arResult["TEMPLATE_DATA"]["FILES_IN_COMMENTS"] = \Bitrix\Tasks\Integration\Forum\Task\Topic::getFileCount((int)$taskData["ID"]);

$arResult['DATA']['TASK']['~DESCRIPTION'] = $arResult['DATA']['TASK']['DESCRIPTION'];
//$arResult['DATA']['TASK']['DESCRIPTION'] = \Bitrix\Tasks\Util\UI::convertHtmlToSafeHtml($arResult['DATA']['TASK']['DESCRIPTION']);
if($arResult['DATA']['TASK']['DESCRIPTION_IN_BBCODE'] == 'Y')
{
	// convert to bbcode to html to show inside a document body
	$arResult['DATA']['TASK']['DESCRIPTION'] = \Bitrix\Tasks\Util\UI::convertBBCodeToHtml($arResult['DATA']['TASK']['DESCRIPTION'], array(
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
		'USER_FIELDS' => $arResult['AUX_DATA']['USER_FIELDS']
	));
}
else
{
	$arResult['DATA']['TASK']['DESCRIPTION'] = htmlspecialcharsbx($arResult['DATA']['TASK']['DESCRIPTION']);
}

if (!empty($arResult['DATA']['TASK']['DESCRIPTION']))
{
	$arResult['DATA']['TASK']['DESCRIPTION'] = preg_replace_callback(
		'|<a href="/bitrix/tools/bizproc_show_file.php\?([^"]+)[^>]+>|',
		function($matches)
		{
			parse_str(htmlspecialcharsback($matches[1]), $query);
			$filename = '';
			if (isset($query['f']))
			{
				$query['hash'] = md5($query['f']);
				$filename = $query['f'];
				unset($query['f']);
			}
			$query['mobile_action'] = 'bp_show_file';
			$query['filename'] = $filename;

			return '<a href="#" data-url="'.SITE_DIR.'mobile/ajax.php?'.http_build_query($query)
				.'" data-name="'.htmlspecialcharsbx($filename).'" onclick="BXMobileApp.UI.Document.open({url: this.getAttribute(\'data-url\'), filename: this.getAttribute(\'data-name\')}); return false;">';
		},
		$arResult['DATA']['TASK']['DESCRIPTION']
	);
}

// todo: remove this when tasksRenderJSON() removed
if(is_array($arResult['DATA']['EVENT_TASK']))
{
	// It seems DESCRIPTION is not used anywhere, so to avoid security problems, simply dont pass DESCRIPTION.
	unset($arResult['DATA']['EVENT_TASK']['DESCRIPTION']);
	// the rest of array should be safe, as expected by tasksRenderJSON()
	$arResult['DATA']['EVENT_TASK_SAFE'] = \Bitrix\Tasks\Util::escape($arResult['DATA']['EVENT_TASK']);
}


$task = &$arResult["DATA"]["TASK"];
$arParams['AVATAR_SIZE'] = ($arParams['AVATAR_SIZE'] ?: 58);
if (!array_key_exists("ID", $task))
{
	$task["ID"] = 0;
	$task["TITLE"] = "";
	$task["DESCRIPTION"] = "";
	$task["DECLINE_REASON"] = "";
	$task["STATUS"] = 0;
}

$users = array(
	$task["RESPONSIBLE_ID"] => array(
		"ID" => $task["RESPONSIBLE_ID"],
		"NAME" => $task["RESPONSIBLE_NAME"],
		"LAST_NAME" => $task["RESPONSIBLE_LAST_NAME"],
		"SECOND_NAME" => $task["RESPONSIBLE_SECOND_NAME"],
		"LOGIN" => $task["RESPONSIBLE_LOGIN"],
		"PERSONAL_PHOTO" => $task["RESPONSIBLE_PHOTO"]
	),
	$task["CREATED_BY"] => array(
		"ID" => $task["CREATED_BY"],
		"NAME" => $task["CREATED_BY_NAME"],
		"LAST_NAME" => $task["CREATED_BY_LAST_NAME"],
		"SECOND_NAME" => $task["CREATED_BY_SECOND_NAME"],
		"LOGIN" => $task["CREATED_BY_LOGIN"],
		"PERSONAL_PHOTO" => $task["CREATED_BY_PHOTO"]
	)
);
foreach ($task["SE_ACCOMPLICE"] as $user)
	$users[$user["ID"]] = $user;
foreach ($task["SE_AUDITOR"] as $user)
	$users[$user["ID"]] = $user;

foreach ($users as &$user)
{
	$user["NAME"] = CUser::FormatName($arParams["NAME_TEMPLATE"], $user, true, false);
	$user["AVATAR"] = "";
	if ($user["PERSONAL_PHOTO"] && ($file = CFile::GetFileArray($user["PERSONAL_PHOTO"])) && $file !== false)
	{
		$arFileTmp = CFile::ResizeImageGet(
			$file,
			array(
				"width"  => $arParams['AVATAR_SIZE'],
				"height" => $arParams['AVATAR_SIZE']
			),
			BX_RESIZE_IMAGE_EXACT,
			false
		);
		$user["AVATAR"] = $arFileTmp['src'];
	}
}

$task["SE_RESPONSIBLE"] = $users[$task["RESPONSIBLE_ID"]];
$task["SE_ORIGINATOR"] = $users[$task["CREATED_BY"]];
$task["SE_ACCOMPLICE"] = array();
$task["SE_AUDITOR"] = array();

foreach ($task["ACCOMPLICES"] as $id)
	$task["SE_ACCOMPLICE"][$id] = $users[$id];
foreach ($task["AUDITORS"] as $id)
	$task["SE_AUDITOR"][$id] = $users[$id];
if (array_key_exists("GROUP", $arResult["DATA"]) && is_array($arResult["DATA"]["GROUP"]))
{
	foreach ($arResult["DATA"]["GROUP"] as &$group)
	{
		$arFileTmp = CFile::ResizeImageGet(
			$group["IMAGE_ID"],
			array(
				"width"  => $arParams['AVATAR_SIZE'],
				"height" => $arParams['AVATAR_SIZE']
			),
			BX_RESIZE_IMAGE_EXACT,
			false
		);
		$group["AVATAR"] = $arFileTmp['src'];
	}
}
$task["SE_CHECKLIST"] = (is_array($task["SE_CHECKLIST"]) ? $task["SE_CHECKLIST"] : array());

// sonet log
$arResult["TEMPLATE_DATA"]["LOG_ID"] = false;

if (CModule::includeModule('socialnetwork'))
{
	$res = CSocNetLog::getList(
		array(),
		array(
			'EVENT_ID' => 'tasks',
			'SOURCE_ID' => $taskData["ID"]
		),
		false,
		false,
		array('ID')
	);
	if ($item = $res->fetch())
	{
		$arResult["TEMPLATE_DATA"]["LOG_ID"] = intval($item['ID']);
	}

	if (
		!$arResult["TEMPLATE_DATA"]["LOG_ID"]
		&& CModule::includeModule('crm')
	)
	{
		$res = CCrmActivity::getList(
			array(),
			array(
				'TYPE_ID' => \CCrmActivityType::Task,
				'ASSOCIATED_ENTITY_ID' => $taskData["ID"],
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			false,
			array('ID')
		);
		if ($crmActivity = $res->fetch())
		{
			$res = CSocNetLog::getList(
				array(),
				array(
					'EVENT_ID' => 'crm_activity_add',
					'ENTITY_ID' => $crmActivity['ID']
				),
				false,
				false,
				array('ID')
			);
			if ($item = $res->fetch())
			{
				$arResult["TEMPLATE_DATA"]["LOG_ID"] = intval($item['ID']);
			}
		}
	}
}
$arResult["TEMPLATE_DATA"]["CURRENT_TS"] = time();

if (!empty($arParams['TOP_RATING_DATA']))
{
	$arResult['TOP_RATING_DATA'] = $arParams['TOP_RATING_DATA'];
}
elseif (!empty($arResult["TEMPLATE_DATA"]["LOG_ID"]))
{
	$ratingData = \Bitrix\Socialnetwork\ComponentHelper::getLivefeedRatingData(array(
		'topCount' => 10,
		'logId' => array($arResult["TEMPLATE_DATA"]["LOG_ID"]),
	));

	if (
		!empty($ratingData)
		&& !empty($ratingData[$arResult["TEMPLATE_DATA"]["LOG_ID"]])
	)
	{
		$arResult['TOP_RATING_DATA'] = $ratingData[$arResult["TEMPLATE_DATA"]["LOG_ID"]];
	}
}

$arResult["RATING"] = \CRatings::getRatingVoteResult("TASK", $taskData["ID"]);

