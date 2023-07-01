<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\UI;
use \Bitrix\Tasks\Util\Type;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var TasksBaseComponent $component */

Loc::loadMessages(__DIR__.'/template.php');

\Bitrix\Main\UI\Extension::load(["ui.notification", "ui.design-tokens"]);

if (!intval($arParams['ID']))
{
	$arResult["ERROR"][] = array(
		"TYPE" => "FATAL",
		"MESSAGE" => Loc::getMessage("TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE")
	);

	if($arParams["SET_TITLE"] === 'Y')
	{
		$APPLICATION->SetTitle(Loc::getMessage("TASKS_TT_VIEW"));
	}
}

$arResult["TEMPLATE_DATA"] = array();
if (!empty($arResult["ERROR"]))
{
	$hasFatals = false;
	foreach($arResult["ERROR"] as $error)
	{
		if ($error["TYPE"] === "FATAL")
		{
			$arResult["TEMPLATE_DATA"]["ERROR"][] = $error['MESSAGE'];
			$hasFatals = true;
		}
	}

	if($hasFatals)
	{
		return;
	}
}

$taskData = $arResult["DATA"]["TASK"];
$can = $arResult["CAN"]["TASK"]["ACTION"];

$folder = $this->GetFolder();
CJSCore::RegisterExt(
	"task_detail",
	array(
		"js"  => $folder."/logic.js",
		"rel" =>  array(
			'ui.design-tokens',
			'ui.fonts.opensans',
			'tasks_util',
			'tasks_util_widget',
			'tasks_util_itemset',
			'tasks_util_query',
			"tasks_itemsetpicker",
			'tasks',
		),
	)
);

CJSCore::Init("task_detail");

//Only for previous tasks table
$APPLICATION->SetAdditionalCSS('/bitrix/js/tasks/css/tasks.css');

$component = $this->__component;

//Public Mode
$arParams["PUBLIC_MODE"] = $component->tryParseBooleanParameter($arParams["PUBLIC_MODE"], false);

//User Name Template
$arParams["NAME_TEMPLATE"] = empty($arParams["NAME_TEMPLATE"]) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arResult["TEMPLATE_DATA"]["NAME_TEMPLATE"] = $arParams["NAME_TEMPLATE"];

//Do we have to show user fields?
$arResult["TEMPLATE_DATA"]["SHOW_USER_FIELDS"] = false;
$userFields = isset($arResult["AUX_DATA"]["USER_FIELDS"]) ? $arResult["AUX_DATA"]["USER_FIELDS"] : array();
foreach($userFields as $fieldId => $field)
{
	if (
		$field["VALUE"] !== false &&
		(!empty($field["VALUE"]) || $field["VALUE"] == 0) &&
		$fieldId !== \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode()
	)
	{
		$arResult["TEMPLATE_DATA"]["SHOW_USER_FIELDS"] = true;
		break;
	}
}

$taskType = $arParams["GROUP_ID"] > 0 ? "group" : "user";
$arResult["TEMPLATE_DATA"]["TASK_TYPE"] = $taskType;

//User Paths
$component->tryParseStringParameter(
	$arParams["PATH_TO_USER_TASKS"],
	$arParams["PUBLIC_MODE"] ? "" : COption::GetOptionString("tasks", "paths_task_user", null, SITE_ID)
);

$component->tryParseStringParameter(
	$arParams["PATH_TO_USER_TASKS_TASK"],
	$arParams["PUBLIC_MODE"] ? "" : COption::GetOptionString("tasks", "paths_task_user_action", null, SITE_ID)
);

//Group Paths
$component->tryParseStringParameter(
	$arParams["PATH_TO_GROUP_TASKS"],
	$arParams["PUBLIC_MODE"] ? "" : COption::GetOptionString("tasks", "paths_task_group", null, SITE_ID)
);

$component->tryParseStringParameter(
	$arParams["PATH_TO_GROUP_TASKS_TASK"],
	$arParams["PUBLIC_MODE"] ? "" : COption::GetOptionString("tasks", "paths_task_group_action", null, SITE_ID)
);

if ($taskType == "user")
{
	$arParams["PATH_TO_TASKS"] = str_ireplace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_ireplace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]);
}
else
{
	$arParams["PATH_TO_TASKS"] = str_ireplace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_ireplace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS_TASK"]);
}

$arParams["PATH_TO_TASKS_WO_GROUP"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS"]);
$arParams["PATH_TO_TASKS_TASK_WO_GROUP"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]);

if (isset($arParams["TASK_URL_PARAMETERS"]) && is_array($arParams["TASK_URL_PARAMETERS"]) && !empty($arParams["TASK_URL_PARAMETERS"]))
{
	if ($arParams["PATH_TO_TASKS_TASK"] !== "")
	{
		$arParams["PATH_TO_TASKS_TASK"] = \Bitrix\Tasks\Util::replaceUrlParameters(
			$arParams["PATH_TO_TASKS_TASK"],
			$arParams["TASK_URL_PARAMETERS"]
		);
	}
}

// Template Paths
$component->tryParseStringParameter($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"], "");
$component->tryParseStringParameter($arParams["PATH_TO_USER_TASKS_TEMPLATES"], "");
$arParams["PATH_TO_TEMPLATES_TEMPLATE"] = str_ireplace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]);
$arParams["PATH_TO_TASKS_TEMPLATES"] = str_ireplace("#user_id#",$arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TEMPLATES"]);

// Title & breadcrumbs
$component->tryParseBooleanParameter($arParams["SET_TITLE"], true);
$component->tryParseBooleanParameter($arParams["SET_NAVCHAIN"], true);

if ($arParams["SET_TITLE"])
{
	$APPLICATION->SetTitle(htmlspecialcharsbx($taskData["TITLE"]));
}

if ($arParams["SET_NAVCHAIN"])
{
	$user = isset($arResult["DATA"]["USER"][$arParams["USER_ID"]]) ? $arResult["DATA"]["USER"][$arParams["USER_ID"]] : false;
	$group = isset($arResult["DATA"]["GROUP"][$arParams["GROUP_ID"]]) ? $arResult["DATA"]["GROUP"][$arParams["GROUP_ID"]] : false;
	if ($taskType == "user" && $user)
	{
		$APPLICATION->AddChainItem(
			CUser::FormatName($arParams["NAME_TEMPLATE"], $user),
			CComponentEngine::makePathFromTemplate(
				$arParams["~PATH_TO_USER_PROFILE"],
				array("user_id" => $arParams["USER_ID"])
			)
		);
		$APPLICATION->AddChainItem($title ?? null);
	}
	elseif ($group)
	{
		$APPLICATION->AddChainItem(
			$group["NAME"],
			CComponentEngine::makePathFromTemplate(
				$arParams["~PATH_TO_GROUP"],
				array("group_id" => $arParams["GROUP_ID"])
			)
		);
		$APPLICATION->AddChainItem($title ?? null);
	}
}

//New Task Path
$arResult["TEMPLATE_DATA"]["NEW_TASK_PATH"] = CComponentEngine::makePathFromTemplate(
	$arParams["PATH_TO_TASKS_TASK"],
	array("task_id" => 0, "action" => "edit")
);

$arResult["TEMPLATE_DATA"]["NEW_SUBTASK_PATH"] =
	$arResult["TEMPLATE_DATA"]["NEW_TASK_PATH"].
	(mb_strpos($arResult["TEMPLATE_DATA"]["NEW_TASK_PATH"], "?") === false ? "?" : "&").
	"PARENT_ID=".$taskData["ID"];

$arResult["TEMPLATE_DATA"]["NEW_TASK_BY_TEMPLATE_PATH"] =
	$arResult["TEMPLATE_DATA"]["NEW_TASK_PATH"] . '?TEMPLATE=';

$arResult["TEMPLATE_DATA"]["NEW_SUB_TASK_BY_TEMPLATE_PATH"] =
	$arResult["TEMPLATE_DATA"]["NEW_SUBTASK_PATH"] . '&TEMPLATE=';

$arResult["TEMPLATE_DATA"]["TASK_TEMPLATES_PATH"] = CComponentEngine::makePathFromTemplate(
	$arParams["PATH_TO_USER_TASKS_TEMPLATES"],
	array("user_id" => \Bitrix\Tasks\Util\User::getId())
);

$arResult["TEMPLATE_DATA"]["TASK_VIEW_PATH"] = CComponentEngine::makePathFromTemplate(
	$arParams["PATH_TO_TASKS_TASK"],
	array("action" => "view", "task_id" => $taskData["ID"])
);

//Tuning
$component->tryParseBooleanParameter($arParams["ENABLE_MENU_TOOLBAR"], true);

//Rating
$arResult["TEMPLATE_DATA"]["RATING"] = CRatings::GetRatingVoteResult("TASK", $taskData["ID"]);

//Body Class
$ownClass = "no-paddings task-detail-page no-background";
$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$bodyClass = $bodyClass ? $bodyClass." ".$ownClass : $ownClass;
$APPLICATION->SetPageProperty("BodyClass", $bodyClass);

//Group
$arResult["TEMPLATE_DATA"]["GROUP_URL_TEMPLATE"] = CComponentEngine::makePathFromTemplate(
	$arParams["PATH_TO_GROUP"],
	array("group_id" => "{{VALUE}}")
);

$arResult["TEMPLATE_DATA"]["GROUP"] = array();
if ($taskData["GROUP_ID"] && isset($arResult["DATA"]["GROUP"][$taskData["GROUP_ID"]]) /*&&
	 CSocNetGroup::CanUserViewGroup($USER->getID(), $taskData["GROUP_ID"])*/)
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
if (!$arParams["PUBLIC_MODE"] &&
	isset($arResult["DATA"]["RELATED_TASK"]) &&
	isset($arResult["DATA"]["RELATED_TASK"][$taskData["PARENT_ID"]])
)
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
$arResult["TEMPLATE_DATA"]["SUBTASKS_EXIST"] = false;
if (!$arParams["PUBLIC_MODE"])
{
	$subtasks = CTasks::GetList(array("GROUP_ID" => "ASC"), array("PARENT_ID" => $taskData["ID"]), array(), array("nPageTop" => 1));
	$arResult["TEMPLATE_DATA"]["SUBTASKS_EXIST"] = $subtasks->fetch() !== false;
}

//Predecessors
$arResult["TEMPLATE_DATA"]["PREDECESSORS"] = array();
if (isset($taskData["SE_PROJECTDEPENDENCE"]))
{
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
}

//Previous Tasks
$arResult["TEMPLATE_DATA"]["PREV_TASKS"] = array();
$prevTaskIds = array();
if (!$arParams["PUBLIC_MODE"])
{
	$prevTasks = CTaskDependence::getList(array(), array("TASK_ID" => $taskData["ID"]));
	while ($item = $prevTasks->fetch())
	{
		$prevTaskIds[] = (int) $item['DEPENDS_ON_ID'];
	}
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

		if (array_key_exists('TITLE', $item))
		{
			$item['TITLE'] = \Bitrix\Main\Text\Emoji::decode($item['TITLE']);
		}
		if (array_key_exists('DESCRIPTION', $item) && $item['DESCRIPTION'] !== '')
		{
			$item['DESCRIPTION'] = \Bitrix\Main\Text\Emoji::decode($item['DESCRIPTION']);
		}

		$arResult["TEMPLATE_DATA"]["PREV_TASKS"][] = $item;
	}
}

//Elapsed Time
$secondsSign = ($taskData["TIME_SPENT_IN_LOGS"] >= 0 ? 1 : -1);
$elapsedHours = (int) $secondsSign * floor(abs($taskData["TIME_SPENT_IN_LOGS"] ?? 0) / 3600);
$elapsedMinutes = ($secondsSign * floor(abs($taskData["TIME_SPENT_IN_LOGS"] ?? 0) / 60)) % 60;
$arResult["TEMPLATE_DATA"]["ELAPSED"] = array(
	"HOURS" => $elapsedHours,
	"MINUTES" => $elapsedMinutes,
	"TIME" => $taskData["TIME_SPENT_IN_LOGS"],
);

//Timer
//$timerData = CTaskTimerManager::getInstance(User::getId())->getLastTimer(false);
//$arResult["TEMPLATE_DATA"]["TIMER"] = $timerData;

$arResult['TEMPLATE_DATA']['TIMER_IS_RUNNING_FOR_CURRENT_USER'] = (($taskData['TIMER_IS_RUNNING'] ?? null) ? 'Y' : 'N');

//Files in Comments
$arResult["TEMPLATE_DATA"]["FILES_IN_COMMENTS"] = \Bitrix\Tasks\Integration\Forum\Task\Topic::getFileCount((int)$taskData["ID"]);

//Description
// todo: remove this when you got array access in $arResult['DATA']['TASK']
if ((string) $arResult["DATA"]["TASK"]["DESCRIPTION"] != "")
{
	if ($arResult["DATA"]["TASK"]["DESCRIPTION_IN_BBCODE"] == "Y")
	{
		// convert to bbcode to html to show inside a document body
		$arResult["DATA"]["TASK"]["DESCRIPTION"] = UI::convertBBCodeToHtml(
			$arResult["DATA"]["TASK"]["DESCRIPTION"],
			array(
				"maxStringLen" => 0,
				"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
				"USER_FIELDS" => $arResult["AUX_DATA"]["USER_FIELDS"]
			)
		);
	}
	else
	{
		// make our description safe to display
		$arResult["DATA"]["TASK"]["DESCRIPTION"] = UI::convertHtmlToSafeHtml(
			$arResult["DATA"]["TASK"]["DESCRIPTION"]
		);
	}
}

// todo: remove this when tasksRenderJSON() removed
if (Type::isIterable($arResult["DATA"]["EVENT_TASK"] ?? null))
{
	// It seems DESCRIPTION is not used anywhere, so to avoid security problems, simply dont pass DESCRIPTION.
	unset($arResult["DATA"]["EVENT_TASK"]["DESCRIPTION"]);
	// the rest of array should be safe, as expected by tasksRenderJSON()
	$arResult["DATA"]["EVENT_TASK_SAFE"] = \Bitrix\Tasks\Util::escape($arResult["DATA"]["EVENT_TASK"]);
}

if (
	!empty($arResult["DATA"]["TASK"])
	&& !empty($arResult["DATA"]["TASK"]["SE_ORIGINATOR"])
	&& is_array($arResult["DATA"]["TASK"]["SE_ORIGINATOR"])
)
{
	$arResult["DATA"]["TASK"]["SE_ORIGINATOR"]["NAME_FORMATTED"] = CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["DATA"]["TASK"]["SE_ORIGINATOR"], true, false);
}

// Prepare params to lazyload
$arResult["PARAMS_TO_LAZY_LOAD_TABS"] = array();
if ($arResult["TEMPLATE_DATA"]["FILES_IN_COMMENTS"] > 0)
{
	$arResult["PARAMS_TO_LAZY_LOAD_TABS"]["files"] = array(
		"TASK_ID" => $arResult["DATA"]["TASK"]["ID"],
		"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
		"FORUM_TOPIC_ID" => $arResult["DATA"]["TASK"]["FORUM_TOPIC_ID"],
		"FORUM_ID" => $arResult["DATA"]["TASK"]["FORUM_ID"]
	);
}

$arResult["LIKE_TEMPLATE"] = (
	$arParams["RATING_TYPE"] == "like"
	&& \Bitrix\Main\ModuleManager::isModuleInstalled('intranet')
		? 'like_react'
		: $arParams["RATING_TYPE"]
);

if (!$arParams["PUBLIC_MODE"])
{
	if (
		$arResult["LIKE_TEMPLATE"] == 'like_react'
		&& empty($arResult['TOP_RATING_DATA'])
	)
	{
		$ratingData = \CRatings::getEntityRatingData(array(
			'entityTypeId' => 'TASK',
			'entityId' => array($arResult["DATA"]["TASK"]["ID"]),
		));

		if (
			!empty($ratingData)
			&& !empty($ratingData[$arResult["DATA"]["TASK"]["ID"]])
		)
		{
			$arResult['TOP_RATING_DATA'] = $ratingData[$arResult["DATA"]["TASK"]["ID"]];
		}
	}

	if (Bitrix\Main\Loader::includeModule('socialnetwork'))
	{
		$arResult['CONTENT_ID'] = 'TASK-' . $arResult['DATA']['TASK']['ID'];

		if (
			($contentViewData = \Bitrix\Socialnetwork\Item\UserContentView::getViewData([
				'contentId' => [ $arResult['CONTENT_ID'] ],
			]))
			&& !empty($contentViewData[$arResult['CONTENT_ID']])
		)
		{
			$arResult['CONTENT_VIEW_CNT'] = (int)$contentViewData[$arResult['CONTENT_ID']]['CNT'];
		}
		else
		{
			$arResult['CONTENT_VIEW_CNT'] = 0;
		}
	}
}