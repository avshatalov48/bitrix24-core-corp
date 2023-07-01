<?php
// DEPRECATED! use tasks.task.lists
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Config\Option;
use Bitrix\Main\Text\Emoji;

if (!CBXFeatures::IsFeatureEnabled('Tasks'))
{
	ShowError(GetMessage('TASKS_MODULE_NOT_AVAILABLE_IN_THIS_EDITION'));
	return;
}

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}

if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SOCNET_MODULE_NOT_INSTALLED"));
	return;
}

if (!CModule::IncludeModule("forum"))
{
	ShowError(GetMessage("FORUM_MODULE_NOT_INSTALLED"));
	return;
}

$loggedInUserId = (int) \Bitrix\Tasks\Util\User::getId();

if ( ! ($loggedInUserId >= 1) )
{
	ShowError(GetMessage('TASKS_ACCESS_DENIED'));
	return;
}

if (
	($_SERVER['REQUEST_METHOD'] === 'POST')
	&& check_bitrix_sessid()
	&& isset($_POST, $_POST['module'], $_POST['action'])
	&& ($_POST['module'] === 'tasks')
)
{
	CUtil::JSPostUnescape();

	switch ($_POST['action'])
	{
		case 'group_action':
			if ( ! isset($_POST['subaction'], $_POST['elements_ids'], $_POST['value']) )
			{
				CTaskAssert::logError('[0x12e5e15a] ');
				break;
			}

			if ($_POST['elements_ids'] === 'all')
			{
				if ( ! isset($_POST['arFilter']))
				{
					CTaskAssert::logError('[0x46ef37f8] ');
					break;
				}

				$arFilter = json_decode($_POST['arFilter'], true);

				if ( ! is_array($arFilter) )
				{
					CTaskAssert::logError('[0x19aa7a1d] ');
					break;
				}

				if (array_key_exists('CHECK_PERMISSIONS', $arFilter))
					unset($arFilter['CHECK_PERMISSIONS']);

				if (count($arFilter) == 0)
				{
					CTaskAssert::logError('[0xe7b4f47e] ');
					return;
				}

				$arFilter['CHECK_PERMISSIONS'] = 'Y';
			}
			else
			{
				$unfilteredTaskIds = array_filter(
					array_map(
						'intval', explode(',', $_POST['elements_ids'])
					)
				);
				if (count($unfilteredTaskIds) == 0)
				{
					CTaskAssert::logError('[0x5f5f7fc7] no items given');
					break;
				}

				$arFilter = array('ID' => $unfilteredTaskIds);
			}

			// Select tasks for ensure access to them for current user
			$arTasksIds = array();
			$rsTasks = CTasks::GetList(array(), $arFilter, array('ID'));
			while ($task = $rsTasks->fetch())
				$arTasksIds[] = (int) $task['ID'];

			$value = null;
			$processedItems = $notProcessedItems = 0;
			switch($_POST['subaction'])
			{
				case 'remove':
					foreach ($arTasksIds as $taskId)
					{
						try
						{
							$oTask = CTaskItem::getInstance($taskId, $loggedInUserId);
							$oTask->delete();
							++$processedItems;
						}
						catch (TasksException $e)
						{
							// some of items can't be delete (no rights, etc)
							++$notProcessedItems;
						}
					}
				break;

				case 'complete':
					foreach ($arTasksIds as $taskId)
					{
						try
						{
							$oTask = CTaskItem::getInstance($taskId, $loggedInUserId);
							$oTask->complete();
							++$processedItems;
						}
						catch (TasksException $e)
						{
							// some of items can't be complete (no rights, etc)
							++$notProcessedItems;
						}
					}
				break;

				case 'change_responsible':
					$value = (int) $_POST['value'];

					foreach ($arTasksIds as $taskId)
					{
						try
						{
							$oTask = CTaskItem::getInstance($taskId, $loggedInUserId);
							$oTask->update(array('RESPONSIBLE_ID' => $value));
							++$processedItems;
						}
						catch (TasksException $e)
						{
							// some of items can't be processed (no rights, etc)
							++$notProcessedItems;
						}
					}
				break;

				case 'change_originator':
					$value = (int) $_POST['value'];

					foreach ($arTasksIds as $taskId)
					{
						try
						{
							$oTask = CTaskItem::getInstance($taskId, $loggedInUserId);
							$oTask->update(array('CREATED_BY' => $value));
							++$processedItems;
						}
						catch (TasksException $e)
						{
							// some of items can't be processed (no rights, etc)
							++$notProcessedItems;
						}
					}
				break;

				case 'add_auditor':
					$value = (int) $_POST['value'];

					foreach ($arTasksIds as $taskId)
					{
						try
						{
							$oTask = CTaskItem::getInstance($taskId, $loggedInUserId);
							$arTask = $oTask->getData(false);
							$arTask['AUDITORS'][] = $value;
							$oTask->update(array('AUDITORS' => $arTask['AUDITORS']));
							++$processedItems;
						}
						catch (TasksException $e)
						{
							// some of items can't be processed (no rights, etc)
							++$notProcessedItems;
						}
					}
				break;

				case 'add_accomplice':
					$value = (int) $_POST['value'];

					foreach ($arTasksIds as $taskId)
					{
						try
						{
							$oTask = CTaskItem::getInstance($taskId, $loggedInUserId);
							$arTask = $oTask->getData(false);
							$arTask['ACCOMPLICES'][] = $value;
							$oTask->update(array('ACCOMPLICES' => $arTask['ACCOMPLICES']));
							++$processedItems;
						}
						catch (TasksException $e)
						{
							// some of items can't be processed (no rights, etc)
							++$notProcessedItems;
						}
					}
				break;

				case 'add_favorite':
				case 'delete_favorite':
					$value = (int) $_POST['value'];

					foreach ($arTasksIds as $taskId)
					{
						try
						{
							$oTask = CTaskItem::getInstance($taskId, $loggedInUserId);
							$f = $_POST['subaction'] == 'add_favorite' ? 'addToFavorite' : 'deleteFromFavorite';
							$arTask = $oTask->$f();
							++$processedItems;
						}
						catch (TasksException $e)
						{
							// some of items can't be processed (no rights, etc)
							++$notProcessedItems;
						}
					}
				break;

				case 'substract_deadline':
					// amount of seconds expected in $_POST['value']
					$value = (-1) * ((int) $_POST['value']);

				case 'adjust_deadline':
					if ($value === null)	// wasn't inited at 'substract_deadline'?
						$value = (int) $_POST['value'];		// amount of seconds expected in $_POST['value']

					foreach ($arTasksIds as $taskId)
					{
						try
						{
							$oTask = CTaskItem::getInstance($taskId, $loggedInUserId);
							$arTask = $oTask->getData(false);

							if ( ! $arTask['DEADLINE'] )
							{
								++$notProcessedItems;
								break;
							}

							$deadline = ConvertTimeStamp(MakeTimeStamp($arTask['DEADLINE']) + $value, 'FULL');

							$oTask->update(array('DEADLINE' => $deadline));
							++$processedItems;
						}
						catch (TasksException $e)
						{
							// some of items can't be processed (no rights, etc)
							++$notProcessedItems;
						}
					}
				break;

				case 'set_deadline':
					$value = $_POST['value'];

					foreach ($arTasksIds as $taskId)
					{
						try
						{
							$oTask = CTaskItem::getInstance($taskId, $loggedInUserId);
							$arTask = $oTask->getData(false);
							$oTask->update(array('DEADLINE' => $value));
							++$processedItems;
						}
						catch (TasksException $e)
						{
							// some of items can't be processed (no rights, etc)
							++$notProcessedItems;
						}
					}
				break;

				case 'set_group':
					$value = (int) $_POST['value'];

					foreach ($arTasksIds as $taskId)
					{
						try
						{
							$oTask = CTaskItem::getInstance($taskId, $loggedInUserId);
							$arTask = $oTask->getData(false);
							$oTask->update(array('GROUP_ID' => $value));
							++$processedItems;
						}
						catch (TasksException $e)
						{
							// some of items can't be processed (no rights, etc)
							++$notProcessedItems;
						}
					}
				break;

				default:
					CTaskAssert::logError('[0x8a1747a5] unknown subaction: ' . $_POST['subaction']);
				break;
			}
		break;

		default:
			CTaskAssert::logError('[0x8b300a99] unknown action: ' . $_POST['action']);
		break;
	}

	LocalRedirect($APPLICATION->GetCurPageParam("", array("sessid")));
}

__checkForum($arParams["FORUM_ID"] ?? null);

$arParams["TASK_VAR"] = trim($arParams["TASK_VAR"] ?? '');
if ($arParams["TASK_VAR"] == '')
	$arParams["TASK_VAR"] = "task_id";

$arParams["GROUP_VAR"] = isset($arParams["GROUP_VAR"]) ? trim($arParams["GROUP_VAR"]) : "";
if ($arParams["GROUP_VAR"] == '')
	$arParams["GROUP_VAR"] = "group_id";

$arParams["ACTION_VAR"] = trim($arParams["ACTION_VAR"] ?? '');
if ($arParams["ACTION_VAR"] == '')
	$arParams["ACTION_VAR"] = "action";

if (($arParams["PAGE_VAR"] ?? null) == '')
	$arParams["PAGE_VAR"] = "page";

if ( ! isset($arParams['USE_FILTER_V2']) )
	$arParams['USE_FILTER_V2'] = (COption::GetOptionString('tasks', '~use_filter_v1', null) != '1');
else
	$arParams['USE_FILTER_V2'] = ($arParams['USE_FILTER_V2'] === 'Y') ? true : false;

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arParams["TASK_ID"] = isset($arParams["TASK_ID"]) ? intval($arParams["TASK_ID"]) : 0;

$arResult["ACTION"] = ($arParams["TASK_ID"] > 0 ? "edit" : "create");

$arParams["USER_ID"] = (int)($arParams["USER_ID"] ?? null) > 0 ? intval($arParams["USER_ID"]) : $loggedInUserId;

$arParams["GROUP_ID"] = isset($arParams["GROUP_ID"]) ? intval($arParams["GROUP_ID"]) : 0;

if ($arParams["GROUP_ID"] > 0)
{
	$featurePerms = CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, array($arParams['GROUP_ID']), 'tasks', 'view_all');

	$bCanViewGroup = is_array($featurePerms) && isset($featurePerms[$arParams['GROUP_ID']]) && $featurePerms[$arParams['GROUP_ID']];

	if ( ! $bCanViewGroup )
	{
		$featurePerms = CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, array($arParams['GROUP_ID']), 'tasks', 'view');
		$bCanViewGroup = is_array($featurePerms) && isset($featurePerms[$arParams['GROUP_ID']]) && $featurePerms[$arParams['GROUP_ID']];
	}

	if ( ! $bCanViewGroup )
	{
		ShowError(GetMessage('TASKS_ACCESS_TO_GROUP_DENIED'));
		return;
	}
}

$bAttachUserFields = false;
if (isset($arParams['ATTACH_USER_FIELDS']) && ($arParams['ATTACH_USER_FIELDS'] === 'Y'))
	$bAttachUserFields = true;

if ($bAttachUserFields)
	$arResult['USER_FIELDS'] = array();
else
	$arResult['USER_FIELDS'] = false;

$arResult["TASK_TYPE"] = $taskType = ($arParams["GROUP_ID"] > 0 ? "group" : "user");

$bExcel = isset($_GET["EXCEL"]) && $_GET["EXCEL"] == "Y";

//>>> FINISHED HERE

$oListState = CTaskListState::getInstance(\Bitrix\Tasks\Util\User::getId());

// disable section\role saving
//$oListState->setSection(CTaskListState::VIEW_SECTION_ROLES);
//$oListState->setUserRole(CTaskListState::VIEW_ROLE_RESPONSIBLE);

// backward compatibility begin
$viewType = "tree";
if (
	(isset($_GET["VIEW"]) && $_GET["VIEW"] == "1")
	|| $bExcel
	|| (isset($_GET["VIEW"]) && $_GET["VIEW"] == "2")
)
{
	$viewType = "list";
	try
	{
		$oListState->setSection(CTaskListState::VIEW_SECTION_ROLES);
		$oListState->setUserRole(CTaskListState::VIEW_ROLE_RESPONSIBLE);
		$oListState->setViewMode(CTaskListState::VIEW_MODE_LIST);
		$oListState->switchOffSubmode(CTaskListState::VIEW_SUBMODE_WITH_SUBTASKS);
		$oListState->switchOnSubmode(CTaskListState::VIEW_SUBMODE_WITH_GROUPS);
		$oListState->saveState();
	}
	catch (TasksException $e)
	{
		CTaskAssert::logError('[0xe9474b52] ');
	}
}
elseif(isset($_GET["VIEW"]) && $_GET["VIEW"] == "0")
{
	$viewType = "tree";
	try
	{
		$oListState->setSection(CTaskListState::VIEW_SECTION_ROLES);
		$oListState->setUserRole(CTaskListState::VIEW_ROLE_RESPONSIBLE);
		$oListState->setViewMode(CTaskListState::VIEW_MODE_LIST);
		$oListState->switchOnSubmode(CTaskListState::VIEW_SUBMODE_WITH_SUBTASKS);
		$oListState->switchOnSubmode(CTaskListState::VIEW_SUBMODE_WITH_GROUPS);
		$oListState->saveState();
	}
	catch (TasksException $e)
	{
		CTaskAssert::logError('[0xaf10900e] ');
	}
}
elseif (isset($arParams['VIEW_MODE']))
{
	if ($arParams['VIEW_MODE'] === 'list')
		$viewType = 'list';
}
// backward compatibility end

// get initial state
$arResult['VIEW_STATE'] = $oListState->getState();

if (isset($_GET['F_SECTION']))
{
	if ($_GET['F_SECTION'] == 'ROLES')
	{
		$oListState->setSection(CTaskListState::VIEW_SECTION_ROLES);
		$oListState->saveState();
	}
	elseif ($_GET['F_SECTION'] == 'ADVANCED')
	{
		$oListState->setSection(CTaskListState::VIEW_SECTION_ADVANCED_FILTER);
		$oListState->saveState();

		// if currently selected preset is a SPECIAL preset - drop selected preset to default CTaskFilterCtrl::STD_PRESET_ALIAS_TO_DEFAULT

		if($arParams['USE_FILTER_V2'])
		{
			$oFilter = CTaskFilterCtrl::GetInstance($arParams['USER_ID'], $taskType === 'group');

			$curFilterId = $oFilter->GetSelectedFilterPresetId();

			if (intval($curFilterId) && isset($arResult['VIEW_STATE']['SPECIAL_PRESETS'][$curFilterId]))
			{
				$oFilter->SwitchFilterPreset(CTaskFilterCtrl::STD_PRESET_ALIAS_TO_DEFAULT);
			}
		}
	}
}
elseif (isset($_GET['F_ADVANCED']) && ($_GET['F_ADVANCED'] === 'Y'))
{
	$oListState->setSection(CTaskListState::VIEW_SECTION_ADVANCED_FILTER);
	$oListState->saveState();
}
elseif (isset($_GET['F_FILTER_SWITCH_PRESET']))
{
	$oListState->setSection(CTaskListState::VIEW_SECTION_ADVANCED_FILTER);
	$oListState->saveState();
}

$arSwitchStateTo = array();
if (
	isset($_GET['F_STATE'])
	&& (
		is_array($_GET['F_STATE'])
		|| (mb_strlen($_GET['F_STATE']) > 2)
	)
)
{
	$arSwitchStateTo = (array) $_GET['F_STATE'];
}
elseif ( ! (isset($_GET["F_CANCEL"]) || isset($_GET['F_FILTER_SWITCH_PRESET'])))
{
	if($arResult['VIEW_STATE']['SECTION_SELECTED']['ID'] == CTaskListState::VIEW_SECTION_ROLES)
	{
		$currentRole = $oListState->getUserRole();
		$arSwitchStateTo = array(intval($currentRole) ? 'sR'.base_convert($currentRole, 10, 32) : 'sR400');
	}
}

if ( ! (isset($_GET['VIEW']) || isset($arParams['VIEW_MODE'])) )
{
	foreach ($arSwitchStateTo as $switchStateTo)
	{
		if ($switchStateTo)
		{
			try
			{
				$symbol = mb_substr($switchStateTo, 0, 2);
				$value = CTaskListState::decodeState(mb_substr($switchStateTo, 2));

				switch ($symbol)
				{
					case 'sR':	// set role
						$oListState->setSection(CTaskListState::VIEW_SECTION_ROLES);
						$oListState->setUserRole($value);
						$oListState->setTaskCategory(CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS);
					break;

					case 'sV':	// set view
						$oListState->setViewMode($value);
					break;

					case 'sC':	// set category
						$oListState->setTaskCategory($value);
					break;

					case 'eS':	// enable submode
						$oListState->switchOnSubmode($value);
					break;

					case 'dS':	// disable submode
						$oListState->switchOffSubmode($value);
					break;
				}

				$oListState->saveState();
			}
			catch (TasksException $e)
			{
				CTaskAssert::logError(
					'[0x523d4e28] : $switchStateTo = ' . $switchStateTo . ' (' . $value . ');'
					. ' cur user role: ' . $oListState->getUserRole()
					. ' serialize($arSwitchStateTo) = ' . serialize($arSwitchStateTo)
				);
				// wrong user input, nothing to do here
			}
		}
	}
}

// renew state again
$arResult['VIEW_STATE'] = $oListState->getState();

$oListCtrl = CTaskListCtrl::getInstance($arParams['USER_ID']);
$oListCtrl->useState($oListState);

if ($arParams["GROUP_ID"] > 0)
	$oListCtrl->setFilterByGroupId( (int) $arParams["GROUP_ID"] );
else
	$oListCtrl->setFilterByGroupId(null);

// There is backward compatibility code:
if ( ! (isset($_GET['VIEW']) || isset($arParams['VIEW_MODE'])) )
{
	if (
		$arResult['VIEW_STATE']['VIEW_SELECTED']['ID'] === CTaskListState::VIEW_MODE_LIST
		|| $arResult['VIEW_STATE']['VIEW_SELECTED']['ID'] === CTaskListState::VIEW_MODE_GANTT
	)
	{
		if ($arResult['VIEW_STATE']['SUBMODES']['VIEW_SUBMODE_WITH_SUBTASKS']['SELECTED'] === 'Y')
			$viewType = 'tree';
		else
			$viewType = 'list';
	}
	else
		$viewType = 'tree';
}

$arResult["VIEW_TYPE"] = $viewType;

if (isset($arParams['CONTEXT_ID']))
{
	$columnsContextId = $arParams['CONTEXT_ID'];
}
else
{
	switch ($arResult['VIEW_STATE']['SECTION_SELECTED']['ID'])
	{
		case CTaskListState::VIEW_SECTION_ROLES:
			switch ($arResult['VIEW_STATE']['ROLE_SELECTED']['ID'])
			{
				case CTaskListState::VIEW_ROLE_RESPONSIBLE:
					$columnsContextId = CTaskColumnContext::CONTEXT_RESPONSIBLE;
				break;

				case CTaskListState::VIEW_ROLE_ORIGINATOR:
					$columnsContextId = CTaskColumnContext::CONTEXT_ORIGINATOR;
				break;

				case CTaskListState::VIEW_ROLE_ACCOMPLICE:
					$columnsContextId = CTaskColumnContext::CONTEXT_ACCOMPLICE;
				break;

				case CTaskListState::VIEW_ROLE_AUDITOR:
					$columnsContextId = CTaskColumnContext::CONTEXT_AUDITOR;
				break;

				default:
					$columnsContextId = CTaskColumnContext::CONTEXT_ALL;
				break;
			}
		break;

		case CTaskListState::VIEW_SECTION_ADVANCED_FILTER:
		default:
			$columnsContextId = CTaskColumnContext::CONTEXT_ALL;
		break;
	}
}

$arResult['COLUMNS_CONTEXT_ID'] = $columnsContextId;
$oColumnPreset = CTaskColumnPresetManager::getInstance($loggedInUserId, $columnsContextId);
$oColumnManager = new CTaskColumnManager($oColumnPreset);
$arResult['COLUMNS'] = $oColumnManager->getCurrentPresetColumns();
$arResult['KNOWN_COLUMNS'] = CTaskColumnList::get();

//user paths
// PATH_* block again...
$arParams["PATH_TO_USER_TASKS"] = trim($arParams["PATH_TO_USER_TASKS"] ?? '');
if ($arParams["PATH_TO_USER_TASKS"] == '')
{
	$arParams["PATH_TO_USER_TASKS"] = COption::GetOptionString("tasks", "paths_task_user", null, SITE_ID);
}
$arParams["PATH_TO_USER_TASKS_TASK"] = trim($arParams["PATH_TO_USER_TASKS_TASK"] ?? '');
if ($arParams["PATH_TO_USER_TASKS_TASK"] == '')
{
	$arParams["PATH_TO_USER_TASKS_TASK"] = COption::GetOptionString("tasks", "paths_task_user_action", null, SITE_ID);
}
$arParams["PATH_TO_USER_TASKS_REPORT"] = trim($arParams["PATH_TO_USER_TASKS_REPORT"] ?? '');
if ($arParams["PATH_TO_USER_TASKS_REPORT"] == '')
{
	$arParams["PATH_TO_USER_TASKS_REPORT"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".($arParams["PAGE_VAR"] ?? '')."=user_tasks_report&".($arParams["USER_VAR"] ?? null)."=#user_id#");
}
$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = trim($arParams["PATH_TO_USER_TASKS_TEMPLATES"] ?? '');
if ($arParams["PATH_TO_USER_TASKS_TEMPLATES"] == '')
{
	$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".($arParams["PAGE_VAR"] ?? '')."=user_tasks_templates&".($arParams["USER_VAR"] ?? null)."=#user_id#");
}
$arParams["PATH_TO_USER_PROFILE"] = trim($arParams["PATH_TO_USER_PROFILE"] ?? '');

//group paths
$arParams["PATH_TO_GROUP_TASKS"] = trim($arParams["PATH_TO_GROUP_TASKS"] ?? '');
if ($arParams["PATH_TO_GROUP_TASKS"] == '')
{
	$arParams["PATH_TO_GROUP_TASKS"] = COption::GetOptionString("tasks", "paths_task_group", null, SITE_ID);
}
$arParams["PATH_TO_GROUP_TASKS_TASK"] = trim($arParams["PATH_TO_GROUP_TASKS_TASK"] ?? '');
if ($arParams["PATH_TO_GROUP_TASKS_TASK"] == '')
{
	$arParams["PATH_TO_GROUP_TASKS_TASK"] = COption::GetOptionString("tasks", "paths_task_group_action", null, SITE_ID);
}
$arParams["PATH_TO_GROUP_TASKS_REPORT"] = trim($arParams["PATH_TO_GROUP_TASKS_REPORT"] ?? '');
if ($arParams["PATH_TO_GROUP_TASKS_REPORT"] == '')
{
	$arParams["PATH_TO_GROUP_TASKS_REPORT"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".($arParams["PAGE_VAR"] ?? '')."=group_tasks_report&".$arParams["GROUP_VAR"]."=#group_id#");
}
$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = isset($arParams["PATH_TO_USER_TASKS_TEMPLATES"]) ? trim($arParams["PATH_TO_USER_TASKS_TEMPLATES"]) : "";
$arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"] = isset($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]) ? trim($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]) : "";
if ($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"] == '')
{
	if (!isset($arParams["TEMPLATE_VAR"]))
	{
		$arParams["TEMPLATE_VAR"] = "template_id";
	}
	$arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".($arParams["PAGE_VAR"] ?? '')."=user_templates_template&".($arParams["USER_VAR"] ?? null)."=#user_id#&".$arParams["TEMPLATE_VAR"]."=#template_id#&".$arParams["ACTION_VAR"]."=#action#");
}
$arParams["PATH_TO_TASKS_TEMPLATES"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TEMPLATES"]);
// PATH_* block end

// this should be in template params
$arParams["NAV_TEMPLATE"] = (isset($arParams["NAV_TEMPLATE"]) && $arParams["NAV_TEMPLATE"] <> '' ? $arParams["NAV_TEMPLATE"] : "arrows");

$arResult["ADVANCED_STATUSES"] = array(
	array("TITLE" => GetMessage("TASKS_FILTER_ALL"), "FILTER" => array()),
	array("TITLE" => GetMessage("TASKS_FILTER_ACTIVE"), "FILTER" => array("STATUS" => array(-2, -1, 1, 2, 3))),
	array("TITLE" => GetMessage("TASKS_FILTER_NEW"), "FILTER" => array("STATUS" => array(-2, 1))),
	array("TITLE" => GetMessage("TASKS_FILTER_IN_CONTROL"), "FILTER" => array("STATUS" => array(4, 7))),
	array("TITLE" => GetMessage("TASKS_FILTER_IN_PROGRESS"), "FILTER" => array("STATUS" => 3)),
	array("TITLE" => GetMessage("TASKS_FILTER_ACCEPTED"), "FILTER" => array("STATUS" => 2)),
	array("TITLE" => GetMessage("TASKS_FILTER_OVERDUE"), "FILTER" => array("STATUS" => -1)),
	array("TITLE" => GetMessage("TASKS_FILTER_DELAYED"), "FILTER" => array("STATUS" => 6)),
	array("TITLE" => GetMessage("TASKS_FILTER_DECLINED"), "FILTER" => array("STATUS" => 7)),
	array("TITLE" => GetMessage("TASKS_FILTER_CLOSED"), "FILTER" => array("STATUS" => array(4, 5)))
);

// PATH_* block again
if ($taskType == "user")
{
	$arParams["PATH_TO_TASKS"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]);
	$arParams["PATH_TO_REPORTS"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_REPORT"]);

	$rsUser = CUser::GetByID($arParams["USER_ID"]);
	if ($user = $rsUser->GetNext())
	{
		$arResult["USER"] = $user;
	}
	else
	{
		return;
	}
}
else
{
	$arParams["PATH_TO_TASKS"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS_TASK"]);
	$arParams["PATH_TO_REPORTS"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS_REPORT"]);

	$arResult["GROUP"] = CSocNetGroup::GetByID($arParams["GROUP_ID"]);
	if (!$arResult["GROUP"])
	{
		return;
	}
}

if (!$arResult["USER"])
{
	$rsUser = CUser::GetByID(\Bitrix\Tasks\Util\User::getId());
	$arResult["USER"] = $rsUser->GetNext();
}

$arParams["PATH_TO_TEMPLATES"] = str_replace("#user_id#", \Bitrix\Tasks\Util\User::getId(), $arParams["PATH_TO_USER_TASKS_TEMPLATES"]);
// PATH_* block end

// filter

if ($taskType == "group" || $arParams["USER_ID"] == \Bitrix\Tasks\Util\User::getId())
{
	$arResult["ROLE_FILTER_SUFFIX"] = "";
}
else
{
	if ($arResult["USER"]["PERSONAL_GENDER"] == "F")
	{
		$arResult["ROLE_FILTER_SUFFIX"] = "_F";
	}
	else
	{
		$arResult["ROLE_FILTER_SUFFIX"] = "_M";
	}
}

$arPreDefindFilters = tasksPredefinedFilters($arParams["USER_ID"], $arResult["ROLE_FILTER_SUFFIX"]);

$preDefinedFilterRole = &$arPreDefindFilters["ROLE"];
$preDefinedFilterStatus = &$arPreDefindFilters["STATUS"][0];

if (isset($arParams['COMMON_FILTER']))
	$arCommonFilter = $arParams['COMMON_FILTER'];
else
	$arCommonFilter = $oListCtrl->getCommonFilter();

if ($taskType == "group")
{
	$preDefinedFilterRole[7]["FILTER"] = array();
}

if (isset($_GET["F_SEARCH"]))
{
	if (is_numeric($_GET["F_SEARCH"]) && intval($_GET["F_SEARCH"]) > 0 && ($rsSearch = CTasks::GetByID(intval($_GET["F_SEARCH"]))) && $rsSearch->Fetch())
	{
		$_GET["F_META::ID_OR_NAME"] = intval($_GET["F_SEARCH"]);
	}
	elseif(mb_strlen(trim($_GET["F_SEARCH"])))
	{
		$_GET["F_TITLE"] = $_GET["F_SEARCH"];
	}
	else
	{
		$_GET["F_ADVANCED"] = "N";
		$_SESSION["FILTER"] = array();
	}
}

if (
	(isset($_GET["F_CANCEL"]) && $_GET["F_CANCEL"] == "Y")
	|| ( ! isset($_GET["F_CANCEL"]) )
	|| isset($_GET["FILTERR"])
	|| isset($_GET["FILTERS"])
	|| (isset($_GET["F_ADVANCED"]) && $_GET["F_ADVANCED"] == "Y")
)
{
	$_SESSION["FILTER"] = array();
}

if ((isset($_GET["F_ADVANCED"]) && $_GET["F_ADVANCED"] == "Y") || (isset($_SESSION["FILTER"]["F_ADVANCED"]) && $_SESSION["FILTER"]["F_ADVANCED"] == "Y")) // advanced filter
{
	$arResult["ADV_FILTER"]["F_ADVANCED"] = $_SESSION["FILTER"]["F_ADVANCED"] = "Y";
	$arFilter = array();

	if (intval($fID = tasksGetFilter("F_META::ID_OR_NAME")) > 0)
	{
		$arFilter["META::ID_OR_NAME"] = $fID;
		$arResult["ADV_FILTER"]["F_META::ID_OR_NAME"] = $fID;
	}

	if (intval($fID = tasksGetFilter("F_ID")) > 0)
	{
		$arFilter["ID"] = $fID;
		$arResult["ADV_FILTER"]["F_ID"] = $fID;
	}

	$idsFilter = tasksGetFilter("F_IDS");
	if (is_array($idsFilter) && count($idsFilter) <= 100) // dos protection
	{
		$ids = array();
		foreach($idsFilter as $fID)
		{
			if(intval($fID))
			{
				$ids[] = intval($fID);
			}
		}

		if(!empty($ids))
		{
			$arFilter["ID"] = $ids;
			$arResult["ADV_FILTER"]["F_IDS"] = $ids;
		}
	}

	if ($fTitle = tasksGetFilter("F_TITLE") <> '')
	{
		$arFilter["%TITLE"] = $fTitle;
		$arResult["ADV_FILTER"]["F_TITLE"] = $fTitle;
	}

	if (intval($fResponsible = tasksGetFilter("F_RESPONSIBLE")) > 0)
	{
		$arFilter["RESPONSIBLE_ID"] = $fResponsible;
		$arResult["ADV_FILTER"]["F_RESPONSIBLE"] = $fResponsible;
	}

	if (intval($fCreatedBy = tasksGetFilter("F_CREATED_BY")) > 0)
	{
		$arFilter["CREATED_BY"] = $fCreatedBy;
		$arResult["ADV_FILTER"]["F_CREATED_BY"] = $fCreatedBy;
	}

	if (intval($fAccomplice = tasksGetFilter("F_ACCOMPLICE")) > 0)
	{
		$arFilter["ACCOMPLICE"] = $fAccomplice;
		$arResult["ADV_FILTER"]["F_ACCOMPLICE"] = $fAccomplice;
	}

	if (intval($fAuditor = tasksGetFilter("F_AUDITOR")) > 0)
	{
		$arFilter["AUDITOR"] = $fAuditor;
		$arResult["ADV_FILTER"]["F_AUDITOR"] = $fAuditor;
	}

	if ($fTags = tasksGetFilter("F_TAGS") <> '')
	{
		$arFilter["TAG"] = array_map("trim", explode(",", $fTags));
		$arResult["ADV_FILTER"]["F_TAGS"] = $fTags;
	}

	if ($fDateFrom = tasksGetFilter("F_DATE_FROM") <> '')
	{
		$arFilter[">=CREATED_DATE"] = $fDateFrom;
		$arResult["ADV_FILTER"]["F_DATE_FROM"] = $fDateFrom;
	}

	if ($fDateTo = tasksGetFilter("F_DATE_TO") <> '')
	{
		$arFilter["<=CREATED_DATE"] = $fDateTo;
		$arResult["ADV_FILTER"]["F_DATE_TO"] = $fDateTo;
	}

	if ($fClosedFrom = tasksGetFilter("F_CLOSED_FROM") <> '')
	{
		$arFilter[">=CLOSED_DATE"] = $fClosedFrom;
		$arResult["ADV_FILTER"]["F_CLOSED_FROM"] = $fClosedFrom;
	}

	if ($fClosedTo = tasksGetFilter("F_CLOSED_TO") <> '')
	{
		$arFilter["<=CLOSED_DATE"] = $fClosedTo;
		$arResult["ADV_FILTER"]["F_CLOSED_TO"] = $fClosedTo;
	}

	if ($fActiveFrom = tasksGetFilter("F_ACTIVE_FROM") <> '')
	{
		$arFilter["ACTIVE"]["START"] = $fActiveFrom;
		$arResult["ADV_FILTER"]["F_ACTIVE_FROM"] = $fActiveFrom;
	}

	if ($fActiveTo = tasksGetFilter("F_ACTIVE_TO") <> '')
	{
		$arFilter["ACTIVE"]["END"] = $fActiveTo;
		$arResult["ADV_FILTER"]["F_ACTIVE_TO"] = $fActiveTo;
	}

	if (($fStatus = tasksGetFilter("F_STATUS")) && array_key_exists($fStatus, $arResult["ADVANCED_STATUSES"]) > 0)
	{
		$arFilter = array_merge($arFilter, $arResult["ADVANCED_STATUSES"][$fStatus]["FILTER"]);
		$arResult["ADV_FILTER"]["F_STATUS"] = $fStatus;
	}

	if ($_GET["F_SUBORDINATE"] == "Y")
	{
		$arResult["ADV_FILTER"]["F_SUBORDINATE"] = "Y";
		$arResult["ADV_FILTER"]["F_ANY_TASK"] = "N";

		// Don't set SUBORDINATE_TASKS for admin, it will cause all tasks to be showed
		if ( ! \Bitrix\Tasks\Util\User::isSuper() )
			$arFilter["SUBORDINATE_TASKS"] = "Y";
	}
	elseif ($_GET["F_ANY_TASK"] == "Y")
	{
		$arResult["ADV_FILTER"]["F_SUBORDINATE"] = "N";
		$arResult["ADV_FILTER"]["F_ANY_TASK"] = "Y";
	}
	else
	{
		$arFilter["MEMBER"] = $arParams["USER_ID"];
	}

	if ($_GET["F_MARKED"] == "Y")
	{
		$arResult["ADV_FILTER"]["F_MARKED"] = "Y";
		$arFilter["!MARK"] = false;
	}

	if ($_GET["F_OVERDUED"] == "Y")
	{
		$arResult["ADV_FILTER"]["F_OVERDUED"] = "Y";
		$arFilter["OVERDUED"] = "Y";
	}

	if ($_GET["F_IN_REPORT"] == "Y")
	{
		$arResult["ADV_FILTER"]["F_IN_REPORT"] = "Y";
		$arFilter["ADD_IN_REPORT"] = "Y";
	}

	if (intval($fGroupId = tasksGetFilter("F_GROUP_ID")) > 0 && $taskType != "group")
	{
		$arFilter["GROUP_ID"] = $fGroupId;
		$arResult["ADV_FILTER"]["F_GROUP_ID"] = $fGroupId;
	}
}
elseif (isset($arParams["FILTER"]) && is_array($arParams["FILTER"]))
{
	$arFilter = $arParams["FILTER"];
}
elseif ($arParams['USE_FILTER_V2'])
{
	$bGroupMode = ($taskType === 'group');
	$oFilter = CTaskFilterCtrl::GetInstance($arParams['USER_ID'], $bGroupMode);

	if (isset($_GET['F_FILTER_SWITCH_PRESET']))
	{
		$curFilterId = $oFilter->GetSelectedFilterPresetId();
		$newFilterId = (int) $_GET['F_FILTER_SWITCH_PRESET'];

		if ($newFilterId !== $curFilterId)
		{
			try
			{
				$oFilter->SwitchFilterPreset($newFilterId);
			}
			catch (Exception $e)
			{
				$oFilter->SwitchFilterPreset(CTaskFilterCtrl::STD_PRESET_ALIAS_TO_DEFAULT);
			}
		}
	}

	$oListCtrl->useAdvancedFilterObject($oFilter);
	$arFilter = $oListCtrl->getFilter();

	// in terms of the current architecture it`s not quite possible to mix presets with parts of "role" filter, soooo.... meet another spike
	// this will only work for special presets

	if(!empty($arResult['VIEW_STATE']['SPECIAL_PRESET_SELECTED']) && $arResult['VIEW_STATE']['SECTION_SELECTED']['CODENAME'] == 'VIEW_SECTION_ADVANCED_FILTER')
	{
		$filter = CTaskListCtrl::getFilterFor(
			$arParams['USER_ID'], // this will be ignored, so any value would be appropriate here
			CTaskListState::VIEW_ROLE_RESPONSIBLE, // we are talking about "responsible" role here, while working with presets
			$oListState->getTaskCategory() // interesting category
		);
		if(isset($filter['REAL_STATUS']))
		{
			$arFilter['REAL_STATUS'] = $filter['REAL_STATUS'];
		}
	}
	// spike end

	$arResult['SELECTED_PRESET_NAME'] = $oFilter->GetSelectedFilterPresetName();
	$arResult['SELECTED_PRESET_ID']   = $oFilter->GetSelectedFilterPresetId();
}
else  // predefined filter
{
	if ($taskType == "group")
	{
		$roleFilter = 7;
	}
	else
	{
		if (isset($_GET["FILTERR"]) && array_key_exists($_GET["FILTERR"], $preDefinedFilterRole))
		{
			$roleFilter = $_GET["FILTERR"];
		}
		elseif (isset($_SESSION["FILTER"]["FILTERR"]) && array_key_exists($_SESSION["FILTER"]["FILTERR"], $preDefinedFilterRole))
		{
			$roleFilter = $_SESSION["FILTER"]["FILTERR"];
		}
		else
		{
			$roleFilter = 0;
		}
	}
	$_SESSION["FILTER"]["FILTERR"] = $roleFilter;

	$preDefinedFilterRole[$roleFilter]["SELECTED"] = true;

	if ($roleFilter == 4 || $roleFilter == 5)
	{
		$preDefinedFilterStatus = &$arPreDefindFilters["STATUS"][1];
	}

	if (isset($_GET["FILTERS"]) && array_key_exists($_GET["FILTERS"], $preDefinedFilterStatus))
	{
		$statusFilter = $_GET["FILTERS"];
	}
	elseif (isset($_SESSION["FILTER"]["FILTERS"]) && array_key_exists($_SESSION["FILTER"]["FILTERS"], $preDefinedFilterStatus))
	{
		$statusFilter = $_SESSION["FILTER"]["FILTERS"];
	}
	else
	{
		$statusFilter = 0;
	}
	$_SESSION["FILTER"]["FILTERS"] = $statusFilter;

	$preDefinedFilterStatus[$statusFilter]["SELECTED"] = true;

	$arFilter = array_merge($preDefinedFilterRole[$roleFilter]["FILTER"], $preDefinedFilterStatus[$statusFilter]["FILTER"]);
}

$arResult["PREDEFINED_FILTERS"] = array(
	"ROLE" => $preDefinedFilterRole,
	"STATUS" => $preDefinedFilterStatus
);

// renew state again
$arResult['VIEW_STATE'] = $oListState->getState();

$arFilter = array_merge($arFilter, $arCommonFilter);

if (isset($_GET['F_CREATED_BY']) && $_GET['F_CREATED_BY'])
	$arFilter = array_merge($arFilter, array('CREATED_BY' => (int) $_GET['F_CREATED_BY']));

if (isset($_GET['F_RESPONSIBLE_ID']) && $_GET['F_RESPONSIBLE_ID'])
	$arFilter = array_merge($arFilter, array('RESPONSIBLE_ID' => (int) $_GET['F_RESPONSIBLE_ID']));

if (isset($_GET['F_SEARCH_ALT']) && $_GET['F_SEARCH_ALT'])
	$arFilter = array_merge($arFilter, array('META::ID_OR_NAME' => (string) $_GET['F_SEARCH_ALT']));

$arResult["COMMON_FILTER"] = $arCommonFilter;

// order
$sortInOptions = CUserOptions::GetOption(
	'tasks:list:sort',
	'sort' . '_' . $arResult['COLUMNS_CONTEXT_ID'],
	'none',
	$loggedInUserId
);

$arResult['SUPPORTED_FIELDS_FOR_SORT'] = array(
	'ID', 'TITLE', 'DEADLINE', 'CREATED_BY', 'RESPONSIBLE_ID',
	'PRIORITY', 'MARK', 'TIME_ESTIMATE', 'ALLOW_TIME_TRACKING',
	'CREATED_DATE', 'CHANGED_DATE',
	'CLOSED_DATE', 'SORTING', 'REAL_STATUS', 'STATUS_COMPLETE'
);
if (
	isset($_GET["SORTF"])
	&& in_array($_GET["SORTF"], $arResult['SUPPORTED_FIELDS_FOR_SORT'])
	&& isset($_GET["SORTD"])
	&& (
		(
			($_GET['SORTF'] === 'DEADLINE')
			&& in_array($_GET["SORTD"], array("ASC", "DESC", 'ASC,NULLS', 'DESC,NULLS'))
		)
		|| in_array($_GET["SORTD"], array("ASC", "DESC"))
	)
)
{
	$arOrder = array($_GET["SORTF"] => $_GET["SORTD"]);

	$arOrderSerialized = serialize($arOrder);
	if ($sortInOptions !== $arOrderSerialized)
	{
		CUserOptions::SetOption(
			'tasks:list:sort',
			'sort' . '_' . $arResult['COLUMNS_CONTEXT_ID'],
			$arOrderSerialized,
			false,				// bCommon
			$loggedInUserId
		);
	}
}
elseif (isset($arParams["ORDER"]))
{
	$arOrder = $arParams["ORDER"];
}
else
{
	if ($sortInOptions === "none")
	{
		$arOrder = array("SORTING" => "ASC");
	}
	else
	{
		$arOrder = unserialize($sortInOptions, ['allowed_classes' => false]);
	}
}
CPageOption::SetOptionString("main", "nav_page_in_session", "N");
$arResult['SORTF'] = $arResult['SORTD'] = null;

// $arOrder can contain muliple sort condtions, so we take the first we know an use it as SORTF\SORTD
if(is_array($arOrder))
{
	foreach($arOrder as $sortF => $sortD)
	{
		if(in_array($sortF, $arResult['SUPPORTED_FIELDS_FOR_SORT']))
		{
			$arResult['SORTF'] = $sortF;
			$arResult['SORTD'] = $sortD;
			break;
		}
	}
}

// now modify sorting as required to get REAL sort
if(!is_array($arParams['PREORDER'] ?? null))
{
	$arParams['PREORDER'] = array();
}
foreach($arParams['PREORDER'] as $sortF => $sortD)
{
	if(preg_match('#^[a-zA-Z0-9_-]+$#', $sortF))
	{
		$arParams['PREORDER'][$sortF] = ToLower($sortD) == 'asc' ? 'ASC' : 'DESC';
	}
	else
	{
		unset($arParams['PREORDER'][$sortF]);
	}
}

if ($arResult['SORTF'] === "SORTING")
{
	$arOrder = array(
		"GROUP_ID" => "ASC",
		"SORTING" => "ASC",
		"STATUS_COMPLETE" => "ASC",
		"DEADLINE" => "ASC,NULLS",
		"ID" => "ASC",
	);

	if ($arResult["TASK_TYPE"] === "group")
	{
		unset($arOrder["GROUP_ID"]);
	}
}
else
{
	$arOrder = array_merge($arParams['PREORDER'], $arOrder);
	if (($arParams['SKIP_GROUP_SORT'] ?? null) !== 'Y')
	{
		$arOrder = array_merge(['GROUP_ID' => 'ASC'], $arOrder);
	}
}

//Sorting selector
$arResult["SORTING"] = array();

array_walk($arResult["KNOWN_COLUMNS"], function(&$item, $index) {
	$item["INDEX"] = $index;
});

$sortingColumns = array_merge(
	array("SORTING" => array("DB_COLUMN" => "SORTING", "SORTABLE" => true, "INDEX" => "SORTING")),
	$arResult["KNOWN_COLUMNS"]
);

foreach ($sortingColumns as $column)
{
	if (!in_array($column["DB_COLUMN"], $arResult["SUPPORTED_FIELDS_FOR_SORT"], true))
	{
		continue;
	}

	$ascDirection = mb_stripos(($arResult["SORTD"] ?? ''), "ASC") !== false;

	$defaultDirection = "ASC";
	$reverseDirection = "DESC";
	if ($column["DB_COLUMN"] === "DEADLINE")
	{
		$defaultDirection .= ",NULLS";
		$reverseDirection .= ",NULLS";
	}

	$defaultUrl = $APPLICATION->GetCurPageParam("SORTF=".$column["DB_COLUMN"]."&SORTD=".$defaultDirection, array("SORTF", "SORTD"));
	$reverseUrl = $APPLICATION->GetCurPageParam("SORTF=".$column["DB_COLUMN"]."&SORTD=".$reverseDirection, array("SORTF", "SORTD"));

	$arResult["SORTING"][] = array(
		"INDEX" => $column["INDEX"],
		"SELECTED" => $arResult["SORTF"] === $column["DB_COLUMN"],
		"ASC_URL" => $defaultUrl,
		"DESC_URL" => $reverseUrl,
		"ASC_DIRECTION" => $ascDirection
	);
}

$arSelect = array();

// use pagination by default
if ( ! isset($arParams['USE_PAGINATION']) )
	$arParams['USE_PAGINATION'] = 'Y';

$arGetListParams  = array();
$itemsCount       = 10;		// show 10 items by default

if (($arParams['ITEMS_COUNT'] ?? null) > 0)
{
	$itemsCount = (int) abs($arParams['ITEMS_COUNT']);
}

if ( ! $bExcel )
{
	if ($arParams['USE_PAGINATION'] === 'Y')
	{
		$arGetListParams = array(
			'NAV_PARAMS' => array(
				'nPageSize' => $itemsCount,
				'bDescPageNumbering' => false,
				'NavShowAll'         => false,
				'bShowAll'           => false
			)
		);
	}
	else
	{
		// This will be interpreted by CTasks::GetList as nPageTop
		$arGetListParams = array('nPageTop' => $itemsCount);
	}
}

$arGetListParams['LOAD_PARAMETERS'] = true;

$arResult["ORIGINAL_FILTER"] = $arFilter;
$arResult["FILTER"] = $arFilter;
$arResult["ORDER"] = $arOrder;
unset($arResult["FILTER"]["ONLY_ROOT_TASKS"]);

try
{
	$arSelect = array(
		// basic task's data:
		'ID', 'TITLE', 'PRIORITY', 'STATUS', 'REAL_STATUS', 'MULTITASK',
		'DATE_START', 'GROUP_ID', 'DEADLINE',
		'ALLOW_TIME_TRACKING', 'TIME_ESTIMATE', 'TIME_SPENT_IN_LOGS',
		'COMMENTS_COUNT', 'FILES', 'MARK', 'ADD_IN_REPORT', 'SUBORDINATE',
		'CREATED_DATE', 'VIEWED_DATE', 'FORUM_TOPIC_ID', 'END_DATE_PLAN',
		'START_DATE_PLAN', 'CLOSED_DATE', 'PARENT_ID', 'ALLOW_CHANGE_DEADLINE',
		'ALLOW_TIME_TRACKING', 'MATCH_WORK_TIME', 'CHANGED_DATE',
		// ORIGINATOR data:
		'CREATED_BY', 'CREATED_BY_NAME', 'CREATED_BY_LAST_NAME', 'CREATED_BY_SECOND_NAME',
		'CREATED_BY_LOGIN', 'CREATED_BY_WORK_POSITION', 'CREATED_BY_PHOTO',
		// RESPONSIBLE data:
		'RESPONSIBLE_ID', 'RESPONSIBLE_NAME', 'RESPONSIBLE_LAST_NAME', 'RESPONSIBLE_SECOND_NAME',
		'RESPONSIBLE_LOGIN', 'RESPONSIBLE_WORK_POSITION', 'RESPONSIBLE_PHOTO',
		'FAVORITE',
		// extra data
		// TODO: try to select it only when such columns are presents in list
		'UF_CRM_TASK'
	);

	try
	{
		list($arTaskItems, $rsItems) = CTaskItem::fetchList($loggedInUserId, $arOrder, $arFilter, $arGetListParams, $arSelect);
	}
	catch (TasksException $e)
	{
		// Got SQL error for extended filter? Rollback to default filter preset.
		if ($arParams['USE_FILTER_V2'] && ($e->getCode() & TasksException::TE_SQL_ERROR))
		{
			$bGroupMode = ($taskType === 'group');
			$oFilter = CTaskFilterCtrl::GetInstance($arParams['USER_ID'], $bGroupMode);

			// Not default preset? Switch to it.
			if ($oFilter->GetSelectedFilterPresetId() != CTaskFilterCtrl::STD_PRESET_ALIAS_TO_DEFAULT)
			{
				$oFilter->SwitchFilterPreset(CTaskFilterCtrl::STD_PRESET_ALIAS_TO_DEFAULT);
				$arFilter = $oFilter->GetSelectedFilterPresetCondition();
				$arResult['SELECTED_PRESET_NAME'] = $oFilter->GetSelectedFilterPresetName();
				$arResult['SELECTED_PRESET_ID']   = $oFilter->GetSelectedFilterPresetId();

				// Try again to load data
				list($arTaskItems, $rsItems) = CTaskItem::fetchList($loggedInUserId, $arOrder, $arFilter, $arGetListParams, $arSelect);
			}
			else
				throw new TasksException();
		}
		else
			throw new TasksException();
	}
}
catch (Exception $e)
{
	ShowError(GetMessage('TASKS_UNEXPECTED_ERROR'));
	return;
}

$arResult["NAV_STRING"] = $rsItems->GetPageNavString(GetMessage("TASKS_TITLE_TASKS"), $arParams['NAV_TEMPLATE']);
$arResult["NAV_PARAMS"] = $rsItems->getNavParams();

$arResult["FETCH_LIST_PARAMS"] = $arGetListParams;
$arResult["FETCH_LIST_PARAMS"]["NAV_PARAMS"]["iNumPage"] = $rsItems->NavPageNomer;
$arResult["FETCH_LIST_PARAMS"]["NAV_PARAMS"]["NavPageCount"] = $rsItems->NavPageCount;
$arResult["SELECT"] = $arSelect;
$arResult["TASKS"] = array();
$arTasksIDs = array();
$arForumTopicsIDs = array();
$arGroupsIDs = array();
$arViewed = array();

foreach ($arTaskItems as $oTaskItem)
{
	$task = $oTaskItem->getData();

	$taskId = (int) $task['ID'];

	if ($bAttachUserFields)
	{
		$arResult['USER_FIELDS'][$taskId] = $GLOBALS["USER_FIELD_MANAGER"]
			->GetUserFields('TASKS_TASK', $taskId, LANGUAGE_ID);
	}

	$arTasksIDs[] = $taskId;
	if ($task["FORUM_TOPIC_ID"])
		$arForumTopicsIDs[$task["FORUM_TOPIC_ID"]] = $taskId;

	if ($task["GROUP_ID"])
		$arGroupsIDs[] = $task["GROUP_ID"];

	$arViewed[$taskId] = $task["VIEWED_DATE"] ? $task["VIEWED_DATE"] : $task["CREATED_DATE"];

	$task['META:ALLOWED_ACTIONS_CODES'] = $oTaskItem->getAllowedTaskActions();
	$task['META:ALLOWED_ACTIONS'] = $oTaskItem->getAllowedTaskActionsAsStrings();

	$task["FILES"] = array();
	$arResult["TASKS"][$taskId] = $task;
}
$arGroupsIDs = array_unique($arGroupsIDs);

// Fill files list
if (count($arTasksIDs))
{
	$arFiles2TasksMap = array();	// Mapped FILE_ID to array of TASK_ID, that contains this file
	$arFilesIds = array();

	$rsTaskFiles = CTaskFiles::GetList(array(), array("TASK_ID" => $arTasksIDs));
	while ($arTaskFile = $rsTaskFiles->Fetch())
	{
		$fileId = (int) $arTaskFile['FILE_ID'];
		$taskId = (int) $arTaskFile['TASK_ID'];
		$arFilesIds[] = $fileId;

		if ( ! isset($arFiles2TasksMap['f' . $fileId]) )
			$arFiles2TasksMap['f' . $fileId] = array();

		$arFiles2TasksMap['f' . $fileId][] = $taskId;
	}

	$arFilesIds = array_unique($arFilesIds);

	if ( ! empty($arFilesIds) )
	{
		$rsFiles = CFile::GetList(array(), array('@ID' => implode(',', $arFilesIds)));
		while ($arFile = $rsFiles->Fetch())
		{
			$arTasksIdsWithFile = array_unique($arFiles2TasksMap['f' . $arFile['ID']]);

			foreach ($arTasksIdsWithFile as $taskId)
				$arResult['TASKS'][$taskId]['FILES'][] = $arFile;
		}
	}
}

$arResult["GROUPS"] = array();
$arOpenedProjects =  CUserOptions::GetOption("tasks", "opened_projects", array());
if(is_string($arOpenedProjects) && $arOpenedProjects == '')
{
	$arOpenedProjects = array();
}
if ( ! empty($arGroupsIDs) )
{
	$rsGroups = CSocNetGroup::GetList(array("ID" => "ASC"), array("ID" => $arGroupsIDs));
	while($arGroup = $rsGroups->GetNext())
	{
		if (!empty($arGroup['NAME']))
		{
			$arGroup['NAME'] = Emoji::decode($arGroup['NAME']);
		}
		if (!empty($arGroup['DESCRIPTION']))
		{
			$arGroup['DESCRIPTION'] = Emoji::decode($arGroup['DESCRIPTION']);
		}

		$arGroup["EXPANDED"] = array_key_exists($arGroup["ID"], $arOpenedProjects) && $arOpenedProjects[$arGroup["ID"]] == "false" ? false : true;
		$arGroup["CAN_CREATE_TASKS"] = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $arGroup["ID"], "tasks", "create_tasks");
		$arGroup["CAN_EDIT_TASKS"] = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $arGroup["ID"], "tasks", "edit_tasks");
		$arResult["GROUPS"][$arGroup["ID"]] = $arGroup;
	}
}


$arResult["CHILDREN_COUNT"] = array();
$rsChildrenCount = CTasks::GetChildrenCount($arFilter, $arTasksIDs);
if ($rsChildrenCount)
{
	while($arChildrenCount = $rsChildrenCount->Fetch())
	{
		$arResult["CHILDREN_COUNT"]["PARENT_".$arChildrenCount["PARENT_ID"]] = $arChildrenCount["CNT"];
	}
}

$arResult["UPDATES_COUNT"] = CTasks::GetUpdatesCount($arViewed);

// templates for selector in right top corner
$rsTemplates = CTaskTemplates::GetList(
	array("ID" => "DESC"),
	array('BASE_TEMPLATE_ID' => false, '!TPARAM_TYPE' => CTaskTemplates::TYPE_FOR_NEW_USER),
	array('NAV_PARAMS' => array('nTopCount' => 10)),
	array(
		'USER_ID' => $loggedInUserId,
		'USER_IS_ADMIN' => \Bitrix\Tasks\Integration\SocialNetwork\User::isAdmin(),
	)
);
$arResult["TEMPLATES"] = array();
while($template = $rsTemplates->Fetch())
{
	$arResult["TEMPLATES"][] = $template;
}

$sTitle = "";
if ($taskType == "group")
{
	$sTitle = $sTitleShort = GetMessage("TASKS_TITLE_GROUP_TASKS");
}
else
{
	if ($arParams["USER_ID"] == \Bitrix\Tasks\Util\User::getId())
	{
		$sTitle = $sTitleShort = GetMessage("TASKS_TITLE_MY_TASKS");
	}
	else
	{
		$sTitle = CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["USER"], true, false).": ".GetMessage("TASKS_TITLE_TASKS");
		$sTitleShort = GetMessage("TASKS_TITLE_TASKS");
	}
}
if (($arParams["SET_TITLE"] ?? null) == "Y")
{
	if ($arParams["HIDE_OWNER_IN_TITLE"] == "Y")
	{
		$APPLICATION->SetPageProperty("title", $sTitle);
		$APPLICATION->SetTitle($sTitleShort);
	}
	else
	{
		$APPLICATION->SetTitle($sTitle);
	}
}

if (isset($arParams["SET_NAVCHAIN"]) && $arParams["SET_NAVCHAIN"] != "N")
{
	if ($taskType == "user")
	{
		$APPLICATION->AddChainItem(CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["USER"]), CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_USER_PROFILE"], array("user_id" => $arParams["USER_ID"])));
		$APPLICATION->AddChainItem(GetMessage("TASKS_TITLE_TASKS"));
	}
	else
	{
		$APPLICATION->AddChainItem($arResult["GROUP"]["NAME"], CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_GROUP"], array("group_id" => $arParams["GROUP_ID"])));
		$APPLICATION->AddChainItem(GetMessage("TASKS_TITLE_TASKS"));
	}
}

$site = CSite::GetByID(SITE_ID)->fetch();
$weekDay = $site['WEEK_START'];
$weekDaysMap = array(
	'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'
);

$wh = array(
	'HOURS' => array(
		'START' => array('H' => 9, 'M' => 0, 'S' => 0),
		'END' => array('H' => 19, 'M' => 0, 'S' => 0),
	),
	'HOLIDAYS' => array(),
	'WEEKEND' => array('SA', 'SU'),
	'WEEK_START' => (string) $weekDay != '' && isset($weekDaysMap[$weekDay]) ? $weekDaysMap[$weekDay] : 'MO'
);
if(Loader::includeModule('calendar'))
{
	$calendarSettings = CCalendar::GetSettings(array('getDefaultForEmpty' => false));

	if(is_array($calendarSettings['week_holidays']))
	{
		$wh['WEEKEND'] = $calendarSettings['week_holidays'];
	}
	if((string) $calendarSettings['year_holidays'] != '')
	{
		$holidays = explode(',', $calendarSettings['year_holidays']);
		if(is_array($holidays) && !empty($holidays))
		{
			foreach($holidays as $day)
			{
				$day = trim($day);
				list($day, $month) = explode('.', $day);
				$day = intval($day);
				$month = intval($month);

				if($day && $month)
				{
					$wh['HOLIDAYS'][] = array('M' => $month, 'D' => $day);
				}
			}
		}
	}

	$time = explode('.', (string) $calendarSettings['work_time_start']);
	if(intval($time[0]))
	{
		$wh['HOURS']['START']['H'] = intval($time[0]);
	}
	if((int)($time[1] ?? null))
	{
		$wh['HOURS']['START']['M'] = intval($time[1]);
	}

	$time = explode('.', (string) $calendarSettings['work_time_end']);
	if(intval($time[0]))
	{
		$wh['HOURS']['END']['H'] = intval($time[0]);
	}
	if(intval($time[1] ?? null))
	{
		$wh['HOURS']['END']['M'] = intval($time[1]);
	}
}

$arResult['CALENDAR_SETTINGS'] = $wh;
$arResult['COMPANY_WORKTIME'] = $arResult['CALENDAR_SETTINGS']['HOURS'];

if (isset($arParams['FORCE_LIST_MODE']) && ($arParams['FORCE_LIST_MODE'] === 'Y'))
{
	$this->IncludeComponentTemplate();
}
else
{
	if ($bExcel)
	{
		$APPLICATION->RestartBuffer();

		// hack. any '.default' customized template should contain 'excel' page
		$this->__templateName = '.default';

		Header("Content-Type: application/force-download");
		Header("Content-Type: application/octet-stream");
		Header("Content-Type: application/download");
		Header("Content-Disposition: attachment;filename=tasks.xls");
		Header("Content-Transfer-Encoding: binary");

		$this->IncludeComponentTemplate('excel');

		CMain::FinalActions(); // to make events work on bitrix24
		die();
	}
	else
	{
		$this->IncludeComponentTemplate();
	}
}