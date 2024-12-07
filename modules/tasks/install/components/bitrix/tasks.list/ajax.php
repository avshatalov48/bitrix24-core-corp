<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Helper\FilterRegistry;
use Bitrix\Tasks\Integration\CRM\UserField;
use Bitrix\Tasks\Internals\Task\Priority;
use Bitrix\Tasks\Util\User;

define('STOP_STATISTICS',    true);
define('NO_AGENT_CHECK',     true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

define('BX_SECURITY_SHOW_MESSAGE', true);

$SITE_ID = '';
if (isset($_GET["SITE_ID"]) && is_string($_GET['SITE_ID']))
	$SITE_ID = mb_substr(preg_replace("/[^a-z0-9_]/i", "", $_GET["SITE_ID"]), 0, 2);

if ($SITE_ID != '')
	define("SITE_ID", $SITE_ID);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('tasks');

CModule::IncludeModule('socialnetwork');

Loc::loadMessages(__FILE__);

$SITE_ID = isset($_GET["SITE_ID"]) ? $_GET["SITE_ID"] : SITE_ID;

$GROUP_ID = intval($_GET["GROUP_ID"] ?? null) > 0 ? intval($_GET["GROUP_ID"]) : 0;

if (isset($_GET["nt"]))
{
	preg_match_all("/(#NAME#)|(#NOBR#)|(#\/NOBR#)|(#LAST_NAME#)|(#SECOND_NAME#)|(#NAME_SHORT#)|(#SECOND_NAME_SHORT#)|\s|\,/", urldecode($_GET["nt"]), $matches);
	$nameTemplate = implode("", $matches[0]);
}
else
	$nameTemplate = CSite::GetNameFormat(false);

$jsonReply = null;
if (check_bitrix_sessid())
{
	$arPaths = array(
		'PATH_TO_TASKS_TASK'      => null,
		'PATH_TO_USER_PROFILE'    => null,
		'PATH_TO_USER_TASKS_TASK' => null
	);

	if (isset($_POST['path_to_task']))
		$arPaths['PATH_TO_TASKS_TASK'] = $_POST['path_to_task'];

	if (isset($_POST['path_to_user']))
		$arPaths['PATH_TO_USER_PROFILE'] = $_POST['path_to_user'];

	if (isset($_POST['path_to_user_tasks_task']))
		$arPaths['PATH_TO_USER_TASKS_TASK'] = $_POST['path_to_user_tasks_task'];

	try
	{
		if (isset($_POST["id"]) && (int)$_POST["id"] > 0)
		{
			$columnsOrder = null;

			if (isset($_POST['columnsOrder']))
				$columnsOrder = array_map('intval', $_POST['columnsOrder']);

			if ($_POST["mode"] == "load")
			{
				$userId = User::getId();
				$groupId = (array_key_exists('GROUP_ID', $_POST['filter']) ? $_POST['filter']['GROUP_ID'] : 0);
				$subTasksMode = (array_key_exists('PARENT_ID', $_POST['filter']) && count($_POST['filter']) === 1);

				$filterId = FilterRegistry::getId(FilterRegistry::FILTER_GANTT, $groupId);
				$arFilter = ($subTasksMode ? [] : Filter::getInstance($userId, $groupId, $filterId)->process());
				$arFilter['PARENT_ID'] = (int)$_POST['id'];
				$arFilter['CHECK_PERMISSIONS'] = 'Y';
				unset($arFilter['ONLY_ROOT_TASKS']);

				$params = [
					'LOAD_PARAMETERS' => true,
				];
				if ($groupId)
				{
					$params['SORTING_GROUP_ID'] = $groupId;
				}

				$order = ($_POST["order"] ?: []);
				$select = ['*', UserField::getMainSysUFCode()];

				[$tasks, $rsTask] = CTaskItem::fetchList($userId, $order, $arFilter, $params, $select);

				$arTasks = array();
				$arTasksIDs = array();
				$arViewed = array();
				foreach($tasks as $task)
				{
					$task = $task->getData();

					$rsTaskFiles = CTaskFiles::GetList(array(), array("TASK_ID" => $task["ID"]));
					$task["FILES"] = array();
					while ($arTaskFile = $rsTaskFiles->Fetch())
					{
						$rsFile = CFile::GetByID($arTaskFile["FILE_ID"]);
						if ($arFile = $rsFile->Fetch())
						{
							$task["FILES"][] = $arFile;
						}
					}

					$arViewed[$task["ID"]] = $task["VIEWED_DATE"] ? $task["VIEWED_DATE"] : $task["CREATED_DATE"];
					$arTasksIDs[] = $task["ID"];
					$arTasks[$task["ID"]] = $task;
				}

				$bGannt = (isset($_POST['bGannt']) ? (bool)$_POST['bGannt'] : false);
				if($bGannt)
				{
					$res = \Bitrix\Tasks\Task\DependenceTable::getListByLegacyTaskFilter($arFilter);
					while($item = $res->fetch())
					{
						if(isset($arTasks[$item['TASK_ID']]))
						{
							$arTasks[$item['TASK_ID']]['LINKS'][] = array('from' => intval($item['DEPENDS_ON_ID']), 'to' => intval($item['TASK_ID']), 'type' => intval($item['TYPE']));
						}
					}
				}

				unset($arFilter["PARENT_ID"]);
				$rsChildrenCount = CTasks::GetChildrenCount($arFilter, $arTasksIDs);
				$arChildrenCount = [];
				if ($rsChildrenCount)
				{
					while ($arChildrens = $rsChildrenCount->Fetch())
					{
						$arChildrenCount["PARENT_" . $arChildrens["PARENT_ID"]] = $arChildrens["CNT"];
					}
				}

				$arUpdatesCount = CTasks::GetUpdatesCount($arViewed);

				$APPLICATION->RestartBuffer();
				Header('Content-Type: text/html; charset=' . LANG_CHARSET);

				$arGroups = array();

				$i = 0;
				$iMax = count($arTasks);
				$bIsJSON = ($_POST["type"] === "json");
				if ($bIsJSON)
				{
					echo "[";
				}

				$depth = (int) ($_POST['depth'] ?? null) + 1;
				foreach ($arTasks as $task)
				{
					++$i;

					if ($task["GROUP_ID"])
					{
						if ( ! isset($arGroups[$task["GROUP_ID"]]) )
						{
							$arGroups[$task["GROUP_ID"]] = CSocNetGroup::GetByID($task["GROUP_ID"]);
						}

						$arGroup = $arGroups[$task["GROUP_ID"]];
						if ($arGroup)
						{
							$task["GROUP_NAME"] = $arGroup["NAME"];
						}
					}

					if ($bIsJSON)
					{
						tasksRenderJSON(
							$task,
							$arChildrenCount["PARENT_" . $task["ID"]] ?? null,
							$arPaths,
							true,
							$bGannt,
							false,
							$nameTemplate,
							[],
							false,
							['DISABLE_IFRAME_POPUP' => !!$_REQUEST['DISABLE_IFRAME_POPUP']],
						);

						if ($i < $iMax)
						{
							echo ", ";
						}
					}
					else
					{
						$params = array(
							"PATHS"         => $arPaths,
							"PLAIN"         => false,
							"DEFER"         => true,
							"SITE_ID"       => $SITE_ID,
							"TASK_ADDED"    => false,
							'IFRAME'        => 'N',
							"NAME_TEMPLATE" => $nameTemplate,
							'DATA_COLLECTION' => array(
								array(
									"CHILDREN_COUNT"   => $arChildrenCount["PARENT_" . $task["ID"]],
									"DEPTH"            => $depth,
									"UPDATES_COUNT"    => $arUpdatesCount[$task["ID"]],
									"PROJECT_EXPANDED" => true,
									'ALLOWED_ACTIONS'  => null,
									"TASK"             => $task
								)
							)
						);

						if ($columnsOrder !== null)
							$params['COLUMNS_IDS'] = $columnsOrder;

						$APPLICATION->IncludeComponent(
							'bitrix:tasks.list.items', '.default',
							$params, null, array("HIDE_ICONS" => "Y")
						);
					}
				}
				if ($bIsJSON)
				{
					echo "]";
				}
			}
			else
			{
				$oTask = CTaskItem::getInstanceFromPool($_POST['id'], \Bitrix\Tasks\Util\User::getId());
				try
				{
					$arTask = $oTask->getData($bEscape = false);
				}
				catch (TasksException $e)
				{
					$jsonReply = array('status' => 'failure');
					$APPLICATION->RestartBuffer();
					header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
					echo CUtil::PhpToJsObject($jsonReply);
					CMain::FinalActions();
					exit();
				}

				if ($_POST["mode"] == "delete" && $oTask->checkAccess(ActionDictionary::ACTION_TASK_REMOVE))
				{
					$APPLICATION->RestartBuffer();

					$task = new CTasks();
					$rc = $task->Delete(intval($_POST["id"]));

					if ($rc === false)
					{
						$strError = 'Error';
						if($ex = $APPLICATION->GetException())
							$strError = $ex->GetString();

						if ($_POST["type"] == "json")
						{
							echo "['strError' : '"
								. CUtil::JSEscape(htmlspecialcharsbx($strError))
								. "']";
						}
						else
							echo htmlspecialcharsbx($strError);
					}
				}
				elseif ($_POST["mode"] == "reminders")
				{
					CTaskReminders::Delete(array(
						"=TASK_ID" => intval($_POST["id"]),
						"=USER_ID" => \Bitrix\Tasks\Util\User::getId()
					));
					if (isset($_POST["reminders"]))
					{
						$obReminder = new CTaskReminders();
						foreach($_POST["reminders"] as $reminder)
						{
							$arFields = array(
								"TASK_ID" => intval($_POST["id"]),
								"USER_ID" => \Bitrix\Tasks\Util\User::getId(),
								"REMIND_DATE" => $reminder["r_date"],
								"TYPE" => $reminder["type"],
								"TRANSPORT" => $reminder["transport"]
							);
							$obReminder->Add($arFields);
						}
					}
				}
				elseif ($_POST["mode"] == "favorite")
				{
					$add = !!$_POST['add'];

					if($add)
					{
						$oTask->addToFavorite();
					}
					else
					{
						$oTask->deleteFromFavorite();
					}
				}
				else
				{
					$arActionsMap = array(
						'close' => 'complete', 'start' => 'startExecution',
						'accept' => 'accept', 'renew' => 'renew',
						'defer' => 'defer',  'decline' => 'decline',
						'approve' => 'approve', 'disapprove' => 'disapprove',
						'pause' => 'pauseExecution'
					);

					$taskId = (int) filter_input(INPUT_POST, 'id');
					if (
						$_POST['mode'] === 'close'
						&& !\Bitrix\Tasks\Access\TaskAccessController::can(\Bitrix\Tasks\Util\User::getId(), ActionDictionary::ACTION_TASK_COMPLETE_RESULT, $taskId)
					)
					{
						$jsonReply = [
							'status' => 'failure',
							'message' => Loc::getMessage('TASKS_GANTT_RESULT_REQUIRED'),
						];
						$APPLICATION->RestartBuffer();
						header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
						echo CUtil::PhpToJsObject($jsonReply);
						CMain::FinalActions();
						exit();
					}

					$arFields = array();
					if ($_POST["mode"] == "mark" && in_array($_POST["mark"], array("NULL", "P", "N")))
					{
						if ($_POST["mark"] == "NULL")
							$arFields["MARK"] = false;
						else
							$arFields["MARK"] = $_POST["mark"];

						if ($arTask["SUBORDINATE"] == "Y" && $arTask["RESPONSIBLE_ID"] != \Bitrix\Tasks\Util\User::getId() && isset($_POST["report"]))
							$arFields["ADD_IN_REPORT"] = $_POST["report"] == "true" ? "Y" : "N";
					}
					elseif ($_POST["mode"] == "report" && isset($_POST["report"]))
					{
						if ($arTask["SUBORDINATE"] == "Y" && $arTask["RESPONSIBLE_ID"] != \Bitrix\Tasks\Util\User::getId())
							$arFields["ADD_IN_REPORT"] = $_POST["report"] == "true" ? "Y" : "N";
					}
					elseif ($_POST["mode"] == "deadline" && isset($_POST["deadline"]))
					{
						$arFields["DEADLINE"] = $_POST["deadline"] ? $_POST["deadline"] : false;
					}
					elseif ($_POST["mode"] == "priority" && in_array((int)$_POST["priority"], array_values(Priority::getAll()), true))
					{
						$arFields = array("PRIORITY" => $_POST["priority"]);
					}
					elseif ($_POST["mode"] == "spent" && isset($_POST["hours"]))
					{
						$arFields["DURATION_FACT"] = intval($_POST["hours"]);
					}
					elseif ($_POST["mode"] == "plan_dates" && (isset($_POST["start_date"]) || isset($_POST["end_date"])))
					{
						if ($_POST["start_date"])
							$arFields["START_DATE_PLAN"] = $_POST["start_date"];

						if ($_POST["end_date"])
							$arFields["END_DATE_PLAN"] = $_POST["end_date"];
					}
					elseif ($_POST["mode"] == "tags" && isset($_POST["tags"]))
					{
						$arFields["TAGS"] = $_POST["tags"];
					}
					elseif ($_POST["mode"] == "group" && isset($_POST["groupId"]))
					{
						$arFields["GROUP_ID"] = intval($_POST["groupId"]);
					}
					elseif (isset($arActionsMap[$_POST["mode"]]))	// change status of the task
					{
						$arArgs = array();
						if ($_POST["mode"] === 'decline')
							$arArgs = array($_POST['reason']);

						$jsonReply = array('status' => 'failure');

						call_user_func_array(array($oTask, $arActionsMap[$_POST["mode"]]), $arArgs);

						$jsonReply = array('status' => 'success');
					}
					elseif ($_POST["mode"] == "responsible")
					{
						if ($oTask->checkAccess(ActionDictionary::ACTION_TASK_EDIT))
							$arFields["RESPONSIBLE_ID"] = intval($_POST["responsible"]);
						elseif ($oTask->checkAccess(ActionDictionary::ACTION_TASK_DELEGATE, \Bitrix\Tasks\Access\Model\TaskModel::createFromTaskItem($oTask)))
							$oTask->delegate( (int) $_POST['responsible'] );
					}
					elseif ($_POST["mode"] == "accomplices")
					{
						if (isset($_POST["accomplices"]))
							$arFields["ACCOMPLICES"] = array_filter($_POST["accomplices"]);
						else
							$arFields["ACCOMPLICES"] = array();

						if (!$arFields["ACCOMPLICES"])
							$arFields["ACCOMPLICES"] = array();
					}
					elseif ($_POST["mode"] == "auditors")
					{
						if (isset($_POST["auditors"]))
							$arFields["AUDITORS"] = array_filter($_POST["auditors"]);
						else
							$arFields["AUDITORS"] = array();

						if (!$arFields["AUDITORS"])
							$arFields["AUDITORS"] = array();
					}

					if ( ! empty($arFields) )
						$oTask->update($arFields);

					try
					{
						$task = $oTask->getData();
					}
					catch (TasksException $e)
					{
						$jsonReply = array('status' => 'failure');
						$APPLICATION->RestartBuffer();
						header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
						echo CUtil::PhpToJsObject($jsonReply);
						CMain::FinalActions();
						exit();
					}

					// count children tasks
					$childrenCnt = 0;
					$arFilter = array();
					if (is_array($_POST['arFilter']))
						$arFilter = $_POST['arFilter'];

					if ($rsChildrenCount = CTasks::GetChildrenCount($arFilter, array($task['ID'])))
					{
						if ($arChildrens = $rsChildrenCount->fetch())
							$childrenCnt = $arChildrens["CNT"];
					}

					$bGannt = false;
					if (isset($_POST['bGannt']))
						$bGannt = (bool) $_POST['bGannt'];

					ob_start();
					$params = array(
						"PATHS"         => $arPaths,
						"PLAIN"         => false,
						"DEFER"         => true,
						"SITE_ID"       => $SITE_ID,
						"TASK_ADDED"    => false,
						'IFRAME'        => 'N',
						"NAME_TEMPLATE" => $nameTemplate,
						'DATA_COLLECTION' => array(
							array(
								"CHILDREN_COUNT"   => $childrenCnt,
								"DEPTH"            => 0,
								"UPDATES_COUNT"    => 0,
								"PROJECT_EXPANDED" => true,
								'ALLOWED_ACTIONS'  => null,
								"TASK"             => $task
							)
						)
					);

					if ($columnsOrder !== null)
						$params['COLUMNS_IDS'] = $columnsOrder;

					$APPLICATION->IncludeComponent(
						'bitrix:tasks.list.items', '.default',
						$params, null, array("HIDE_ICONS" => "Y")
					);

					$arAdditionalFields = array('html' => "'" . CUtil::JSEscape(ob_get_clean()) . "'");

					try
					{
						$task = $oTask->getData();
					}
					catch (TasksException $e)
					{
						$jsonReply = array('status' => 'failure');
						$APPLICATION->RestartBuffer();
						header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
						echo CUtil::PhpToJsObject($jsonReply);
						CMain::FinalActions();
						exit();
					}

					ob_start();
					tasksRenderJSON($task, $childrenCnt, $arPaths, true, $bGannt, false, $nameTemplate, $arAdditionalFields);

					if ($jsonReply === null)
					{
						$jsonReply = array(
							'status'          => 'success',
							'tasksRenderJSON' => ob_get_contents()
						);
					}
					else
						$jsonReply['tasksRenderJSON'] = ob_get_contents();

					ob_end_clean();
				}
			}
		}
		elseif (
			$_POST["mode"] == "add"
			&& trim($_POST["title"]) <> ''
			&& intval($_POST["responsible"]) > 0
			&& in_array($_POST["priority"], array(0, 1, 2))
			&& \Bitrix\Tasks\Util\User::isAuthorized()
		)
		{
			$columnsOrder = null;

			if (isset($_POST['columnsOrder']))
				$columnsOrder = array_map('intval', $_POST['columnsOrder']);

			$arFields = array(
				"TITLE" => trim($_POST["title"]),
				"DESCRIPTION" => trim($_POST["description"]),
				"RESPONSIBLE_ID" => intval($_POST["responsible"]),
				"PRIORITY" => $_POST["priority"],
				"SITE_ID" => $SITE_ID,
				"NAME_TEMPLATE" => $nameTemplate,
				'DESCRIPTION_IN_BBCODE' => 'Y'
			);

			if (isset($_POST['group']) && ($_POST['group'] > 0))
				$GROUP_ID = (int) $_POST['group'];

			if ($GROUP_ID > 0)
			{
				if (CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $GROUP_ID, "tasks", "create_tasks"))
					$arFields["GROUP_ID"] = $GROUP_ID;
			}

			if ($DB->FormatDate($_POST["deadline"], CSite::GetDateFormat("FULL")))
			{
				$arFields["DEADLINE"] = $_POST["deadline"];
			}

			$depth = intval($_POST["depth"]);

			if (intval($_POST["parent"]) > 0)
			{
				$arFields["PARENT_ID"] = intval($_POST["parent"]);
			}

			$arFields["STATUS"] = $status;
			$task = new CTasks();
			$ID = $task->Add($arFields);
			if ($ID)
			{
				$rsTask = CTasks::GetByID($ID);
				if ($task = $rsTask->GetNext())
				{
					$APPLICATION->RestartBuffer();

					ob_start();
					if ($task["GROUP_ID"])
					{
						$arGroup = CSocNetGroup::GetByID($task["GROUP_ID"]);
						if ($arGroup)
							$task["GROUP_NAME"] = $arGroup["NAME"];
					}

					$params = array(
						"PATHS"         => $arPaths,
						"PLAIN"         => false,
						"DEFER"         => true,
						"SITE_ID"       => $SITE_ID,
						"TASK_ADDED"    => true,
						'IFRAME'        => 'N',
						"NAME_TEMPLATE" => $nameTemplate,
						'DATA_COLLECTION' => array(
							array(
								"CHILDREN_COUNT"   => 0,
								"DEPTH"            => $depth,
								"UPDATES_COUNT"    => 0,
								"PROJECT_EXPANDED" => true,
								'ALLOWED_ACTIONS'  => null,
								"TASK"             => $task
							)
						)
					);

					$columnsOrder = null;

					if (isset($_POST['columnsOrder']))
						$columnsOrder = array_map('intval', $_POST['columnsOrder']);

					if ($columnsOrder !== null)
						$params['COLUMNS_IDS'] = $columnsOrder;

					$APPLICATION->IncludeComponent(
						'bitrix:tasks.list.items', '.default',
						$params, null, array("HIDE_ICONS" => "Y")
					);

					$html = ob_get_clean();

					if (
						isset($_POST['type'])
						&& (
							($_POST['type'] === 'json_with_html')
							|| ($_POST['type'] === 'json')
						)
					)
					{
						header('Content-Type: text/html; charset=' . LANG_CHARSET);

						$arAdditionalFields = array();
						if ($_POST['type'] === 'json_with_html')
						{
							$arAdditionalFields = array(
								'html' => "'" . CUtil::JSEscape($html) . "'"
							);
						}

						tasksRenderJSON($task, 0, $arPaths, true, true, false, $nameTemplate, $arAdditionalFields);
					}
					else
					{
						header('Content-Type: text/html; charset=' . LANG_CHARSET);
						echo $html;
					}
				}
			}

			CMain::FinalActions(); // to make events work on bitrix24
			exit();
		}
		elseif (isset($_POST['mode']))
		{
			if ($_POST['mode'] === 'resizeColumn')
			{
				CTaskAssert::assert(isset($_POST['columnId'], $_POST['columnWidth'], $_POST['columnContextId']));

				$loggedInUserId  = \Bitrix\Tasks\Util\User::getId();
				$columnId        = (int) $_POST['columnId'];
				$columnWidth     = (int) $_POST['columnWidth'];
				$columnContextId = (int) $_POST['columnContextId'];

				CTaskAssert::assert($loggedInUserId >= 1);
				CTaskAssert::assert(in_array($columnId, array_keys(CTaskColumnList::get())));
				CTaskAssert::assert(in_array($columnContextId, CTaskColumnContext::get()));

				if ($columnWidth < CTaskColumnPresetManager::MINIMAL_COLUMN_WIDTH)
					$columnWidth = CTaskColumnPresetManager::MINIMAL_COLUMN_WIDTH;

				$oPresetManager = CTaskColumnPresetManager::getInstance($loggedInUserId, $columnContextId);
				$oColumnManager = new CTaskColumnManager($oPresetManager);

				$arCurrentColumns = $oColumnManager->getCurrentPresetColumns();

				$columnDataChanged = false;
				foreach ($arCurrentColumns as &$columnData)
				{
					if ($columnData['ID'] == $columnId)
					{
						if ($columnData['WIDTH'] != $columnWidth)
						{
							$columnData['WIDTH'] = $columnWidth;
							$columnDataChanged   = true;
						}

						break;
					}
				}
				unset($columnData);

				if ($columnDataChanged)
					$oColumnManager->setColumns($arCurrentColumns);
			}
			else if ($_POST['mode'] === 'addRemoveColumns')
			{
				CTaskAssert::assert(isset($_POST['selectedColumns'], $_POST['columnContextId']));
				CTaskAssert::assert(is_array($_POST['selectedColumns']));

				$arNewColumns    = array();
				$loggedInUserId  = \Bitrix\Tasks\Util\User::getId();
				$columnContextId = (int) $_POST['columnContextId'];
				$selectedColumns = array_unique(array_map('intval', $_POST['selectedColumns']));

				// Force TITLE column
				if ( ! in_array(CTaskColumnList::COLUMN_TITLE, $selectedColumns, true) )
					$selectedColumns[] = CTaskColumnList::COLUMN_TITLE;

				CTaskAssert::assert($loggedInUserId >= 1);
				CTaskAssert::assert(in_array($columnContextId, CTaskColumnContext::get()));

				$oPresetManager = CTaskColumnPresetManager::getInstance($loggedInUserId, $columnContextId);
				$oColumnManager = new CTaskColumnManager($oPresetManager);

				$arCurrentColumns = $oColumnManager->getCurrentPresetColumns();

				// remove not selected columns
				$arColumnsIds = array();
				foreach ($arCurrentColumns as &$columnData)
				{
					if (
						in_array( (int) $columnData['ID'], $selectedColumns, true)
						&& ( ! in_array( (int) $columnData['ID'], $arColumnsIds, true) )	// prevent duplicates
					)
					{
						$arNewColumns[] = $columnData;
						$arColumnsIds[] = (int) $columnData['ID'];
					}
				}
				unset($columnData);

				// add new columns
				foreach (array_diff($selectedColumns, $arColumnsIds) as $newColumndId)
					$arNewColumns[] = array('ID' => $newColumndId, 'WIDTH' => 0);

				if (empty($arNewColumns))
					$arNewColumns[] = array('ID' => CTaskColumnList::COLUMN_TITLE, 'WIDTH' => 0);

				$oColumnManager->setColumns($arNewColumns);
			}
			else if ($_POST['mode'] === 'resetColumnsToDefault')
			{
				CTaskAssert::assert(isset($_POST['columnContextId']));

				$loggedInUserId  = \Bitrix\Tasks\Util\User::getId();
				$columnContextId = (int) $_POST['columnContextId'];

				CTaskAssert::assert($loggedInUserId >= 1);
				CTaskAssert::assert(in_array($columnContextId, CTaskColumnContext::get()));

				$oPresetManager = CTaskColumnPresetManager::getInstance($loggedInUserId, $columnContextId);
				$oPresetManager->selectPresetId(CTaskColumnPresetManager::PRESET_DEFAULT);

				// reset sort order
				CUserOptions::SetOption(
					'tasks:list:sort',
					'sort' . '_' . $columnContextId,
					'none',
					false,				// bCommon
					$loggedInUserId
				);
			}
			else if ($_POST['mode'] === 'moveColumnAfter')
			{
				CTaskAssert::assert(isset($_POST['movedColumnId'], $_POST['movedAfterColumnId'], $_POST['columnContextId']));

				$loggedInUserId     = \Bitrix\Tasks\Util\User::getId();
				$movedColumnId      = (int) $_POST['movedColumnId'];
				$movedAfterColumnId = (int) $_POST['movedAfterColumnId'];
				$columnContextId    = (int) $_POST['columnContextId'];

				$knownColumnsIds = array_keys(CTaskColumnList::get());

				CTaskAssert::assert($loggedInUserId >= 1);
				CTaskAssert::assert(in_array($movedColumnId, $knownColumnsIds));
				CTaskAssert::assert(($movedAfterColumnId == 0) || in_array($movedAfterColumnId, $knownColumnsIds));
				CTaskAssert::assert(in_array($columnContextId, CTaskColumnContext::get()));

				$oPresetManager = CTaskColumnPresetManager::getInstance($loggedInUserId, $columnContextId);
				$oColumnManager = new CTaskColumnManager($oPresetManager);

				$oColumnManager->moveColumnAfter($movedColumnId, $movedAfterColumnId);
			}
			else if ($_POST['mode'] === 'groupAction')
			{
				CTaskAssert::assert(isset($_POST['action']));
			}
		}
	}
	catch (Exception $e)
	{
		if ($jsonReply === null)
			$jsonReply = array('status' => 'failure');
	}

	if ($jsonReply !== null)
	{
		$APPLICATION->RestartBuffer();
		header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
		echo CUtil::PhpToJsObject($jsonReply);
	}

	CMain::FinalActions(); // to make events work on bitrix24
}