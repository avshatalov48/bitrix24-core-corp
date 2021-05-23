<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Context;

use \Bitrix\Tasks\Util\Error\Collection;
use \Bitrix\Tasks\Manager;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.task");

final class TasksTaskDetailComponent extends TasksTaskComponent
{
	protected static function checkPermissions(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		parent::checkPermissions($arParams, $arResult, $errors, $auxParams);

		if($errors->checkNoFatals())
		{
			if(!($arResult['TASK_INSTANCE'] instanceof CTaskItem)) // task wasnt even checked. absent task is not allowed here
			{
				$errors->add('TASKS_TASK_NOT_FOUND', GetMessage("TASKS_TASK_NOT_FOUND"));
			}
		}

		return $errors->checkNoFatals();
	}

	protected function checkParameters()
	{
		global $APPLICATION;

		$userId = \Bitrix\Tasks\Util\User::getId();

		parent::checkParameters();

		$arParams =& $this->arParams;
		$arResult =& $this->arResult;
		$get = Context::getCurrent()->getRequest()->getQueryList();

		$arParams["SUB_ENTITY_SELECT"] = array(
			"TAG",
			"CHECKLIST",
			"REMINDER",
			"LOG",
			"ELAPSEDTIME",
		);
		$arParams["AUX_DATA_SELECT"] = array(
			"COMPANY_WORKTIME",
			"USER_FIELDS",
		);

		static::tryParseStringParameter($arParams['TASK_VAR'], 'task_id');
		static::tryParseStringParameter($arParams['GROUP_VAR'], 'group_id');
		static::tryParseStringParameter($arParams['ACTION_VAR'], 'action');
		static::tryParseStringParameter($arParams['PAGE_VAR'], 'page');
		static::tryParseStringParameter($arParams['USER_ID'], (int) $userId);
		static::tryParseIntegerParameter($arParams['GROUP_ID'], 0);

		//user pathes
		static::tryParseStringParameter($arParams['PATH_TO_USER_TASKS'], COption::GetOptionString("tasks", "paths_task_user", null, SITE_ID));
		static::tryParseStringParameter($arParams['PATH_TO_USER_TASKS_TASK'], COption::GetOptionString("tasks", "paths_task_user_action", null, SITE_ID));

		//group pathes
		static::tryParseStringParameter($arParams['PATH_TO_GROUP_TASKS'], COption::GetOptionString("tasks", "paths_task_group", null, SITE_ID));
		static::tryParseStringParameter($arParams['PATH_TO_GROUP_TASKS_TASK'], COption::GetOptionString("tasks", "paths_task_group_action", null, SITE_ID));
		static::tryParseStringParameter($arParams['PATH_TO_USER_TASKS_TEMPLATES'], htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_templates&".$arParams["USER_VAR"]."=#user_id#"));
		static::tryParseStringParameter($arParams['PATH_TO_USER_TEMPLATES_TEMPLATE'], htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_templates_template&".$arParams["USER_VAR"]."=#user_id#&".$arParams["TEMPLATE_VAR"]."=#template_id#&".$arParams["ACTION_VAR"]."=#action#"));

		static::tryParseIntegerParameter($arParams['FORUM_ID'], 0);
		if($arParams['FORUM_ID']) // check only forum that came from arParams, not from task->getData()
		{
			__checkForum($arParams["FORUM_ID"]);
		}

		$arParams["PATH_TO_TEMPLATES_TEMPLATE"] = str_replace("#user_id#", $this->userId, $arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]);
		$arParams["PATH_TO_TASKS_TEMPLATES"] = str_replace("#user_id#", $this->userId, $arParams["PATH_TO_USER_TASKS_TEMPLATES"]);

		// Must be equal to MESSAGES_PER_PAGE in mobile.tasks.topic.reviews
		static::tryParseIntegerParameter($arParams['ITEM_DETAIL_COUNT'], 10, true);

		$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

		$arResult["TASK_TYPE"] = ($arParams["GROUP_ID"] > 0 ? "group" : "user");
		$arResult["IS_IFRAME"] = (isset($get["IFRAME"]) && $get["IFRAME"] == "Y");

		if (isset($get["CALLBACK"]) && ($get["CALLBACK"] == "ADDED" || $get["CALLBACK"] == "CHANGED"))
		{
			$arResult["CALLBACK"] = $get["CALLBACK"];
		}

		if (isset($get['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE']) && ($get['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE'] !== ''))
		{
			$arResult['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE'] = array_map('intval', explode(',', $get['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE']));
		}

		if ($arResult["TASK_TYPE"] == "user")
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
				$this->errors->add('USER_NOT_FOUND', 'User not found');
			}
		}
		else
		{
			$arParams["PATH_TO_TASKS"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS"]);
			$arParams["PATH_TO_TASKS_TASK"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS_TASK"]);

			$arResult["GROUP"] = CSocNetGroup::GetByID($arParams["GROUP_ID"]);
			if (!$arResult["GROUP"])
			{
				$this->errors->add('GROUP_NOT_FOUND', 'Group not found');
			}
		}

		if (!$arResult["USER"])
		{
			$rsUser = CUser::GetByID($this->userId);
			$arResult["USER"] = $rsUser->GetNext();
		}

		$arResult['MAX_UPLOAD_FILES_IN_COMMENTS'] = (int) COption::GetOptionString('tasks', 'MAX_UPLOAD_FILES_IN_COMMENTS');

		return $this->errors->checkNoFatals();
	}

	protected function doPostActions()
	{
		parent::doPostActions();

		// for backward compatibility implement exiting on module and\or parameters check failure, even if it stands against architecture
		if($this->errors->checkNoFatals())
		{
			$this->processDelayedFunctions();
		}

		return $this->errors->checkNoFatals();
	}

	protected static function extractParamsFromRequest($request)
	{
		return array('TASK_ID' => $request['TASK_ID']);
	}

	protected function translateTaskDataTags()
	{
		$buffer =& $this->arResult;

		$tags = array();
		if(is_array($buffer['DATA']['TASK'][Manager\Task::SE_PREFIX.'TAG']))
		{
			foreach($buffer['DATA']['TASK'][Manager\Task::SE_PREFIX.'TAG'] as $i => $tag)
			{
				$tags[] = $tag['NAME'];
			}
		}

		$buffer['DATA']['TASK']['~TAGS'] = $tags;
		$buffer['DATA']['TASK']['TAGS'] = array_map('htmlspecialcharsbx', $tags);

		unset($buffer['DATA']['TASK'][Manager\Task::SE_PREFIX.'TAG']);
	}

	protected function translateTaskDataChecklist()
	{
		$buffer =& $this->arResult;

		$task =& $buffer['DATA']['TASK'];
		$buffer['CHECKLIST_ITEMS'] = array();

		if(is_array($task[Manager\Task::SE_PREFIX.'CHECKLIST']))
		{
			foreach($task[Manager\Task::SE_PREFIX.'CHECKLIST'] as $k => &$item)
			{
				$actions = $buffer['CAN']['TASK'][Manager\Task::SE_PREFIX.'CHECKLIST'][$k]['ACTION'];
				if(is_array($actions))
				{
					foreach($actions as $action => $flag)
					{
						$item['META:CAN_'.$action] = $flag;
					}
				}

				$buffer['CHECKLIST_ITEMS'][$k] = $item;
			}
			unset($item);

			unset($task[Manager\Task::SE_PREFIX.'CHECKLIST']);
		}
	}

	protected function translateTaskDataReminders()
	{
		$buffer =& $this->arResult;

		$task =& $buffer['DATA']['TASK'];

		$items = array();
		if(is_array($task[Manager\Task::SE_PREFIX.'REMINDER']))
		{
			foreach($task[Manager\Task::SE_PREFIX.'REMINDER'] as &$item)
			{
				$item['DATE'] = $item['REMIND_DATE'];
				$item = array_change_key_case($item, CASE_LOWER);
			}
		}
		unset($item);

		$buffer['REMINDERS'] = $task[Manager\Task::SE_PREFIX.'REMINDER'];
		unset($task[Manager\Task::SE_PREFIX.'REMINDER']);
	}

	protected function translateTaskDataLog()
	{
		$buffer =& $this->arResult;

		$task =& $buffer['DATA']['TASK'];

		$items = array();
		if(is_array($task[Manager\Task::SE_PREFIX.'LOG']))
		{
			foreach($task[Manager\Task::SE_PREFIX.'LOG'] as $item)
			{
				if (
					isset(CTaskLog::$arComparedFields[$item['FIELD']]) 
					&& (CTaskLog::$arComparedFields[$item['FIELD']] === 'date')
				)
				{
					$item['~TO_VALUE'] = $item['TO_VALUE'];
					$item['~FROM_VALUE'] = $item['FROM_VALUE'];
				}

				$items[] = $item;
			}

			unset($task[Manager\Task::SE_PREFIX.'LOG']);
		}

		$buffer['LOG'] = $items;
	}

	protected function translateTaskDataElapsedTime()
	{
		$buffer =& $this->arResult;

		$task =& $buffer['DATA']['TASK'];

		$items = array();
		$full = 0;
		if(is_array($task[Manager\Task::SE_PREFIX.'ELAPSEDTIME']))
		{
			foreach($task[Manager\Task::SE_PREFIX.'ELAPSEDTIME'] as $k => $item)
			{
				$newItem = $item;
				$full += $item['MINUTES'];

				$can = $buffer['CAN']['TASK'][Manager\Task::SE_PREFIX.'ELAPSEDTIME'][$k]['ACTION'];

				if(is_array($can))
				{
					foreach($can as $action => $flag)
					{
						$newItem['META:CAN_'.$action] = $flag;
					}
				}

				$items[] = $newItem;
			}

			unset($task[Manager\Task::SE_PREFIX.'ELAPSEDTIME']);
		}

		$buffer['FULL_ELAPSED_TIME'] = $full;
		$buffer['ELAPSED_TIME'] = $items;
	}

	protected function getTaskDataRelatedTasks()
	{
		$task =& $this->arResult['TASK'];

		$taskIds = array($task['ID']);

		// subtasks
		$res = CTasks::GetList(array("GROUP_ID" => "ASC"), array("PARENT_ID" => $this->arParams["TASK_ID"]));
		$this->arResult["SUBTASKS"] = array();
		while($item = $res->GetNext())
		{
			$this->arResult["SUBTASKS"][] = $item;
			$taskIds[] = $item["ID"];
			if ($item["GROUP_ID"])
			{
				$this->groups2Get[] = $item["GROUP_ID"];
			}
		}

		// previous tasks
		$res = CTaskDependence::getList(
			array(),
			array('TASK_ID' => $this->arParams["TASK_ID"])
		);
		$prevTaskIds = array();
		while($item = $res->fetch())
		{
			$prevTaskIds[] = (int) $item['DEPENDS_ON_ID'];
		}

		$arResult["PREV_TASKS"] = array();

		if ( ! empty($prevTaskIds) )
		{
			$rsPrevtasks = CTasks::GetList(array('GROUP_ID' => 'ASC'), array('ID' => $prevTaskIds));
			while($item = $rsPrevtasks->GetNext())
			{
				$this->arResult["PREV_TASKS"][] = $item;
				$taskIds[] = $item["ID"];
				if ($item["GROUP_ID"])
				{
					$this->groups2Get[] = $item["GROUP_ID"];
				}
			}
		}

		$res = CTasks::GetChildrenCount(array(), $taskIds);
		if ($res)
		{
			while($cnt = $res->Fetch())
			{
				$this->arResult["CHILDREN_COUNT"]["PARENT_".$cnt["PARENT_ID"]] = $cnt["CNT"];
			}
		}
	}

	/**
	 * Move from a new data appearance to the old one, for keeping backward compatibility
	 */
	protected function getData()
	{
		parent::getData();

		$this->translateTaskDataTags();
		$this->translateTaskDataChecklist();
		$this->translateTaskDataReminders();
		$this->translateTaskDataLog();
		$this->translateTaskDataElapsedTime();

		$arResult =& $this->arResult;
		$arParams =& $this->arParams;

		$arResult['TASK'] = $this->arResult['DATA']['TASK'];
		//unset($arResult['DATA']);
		$task =& $arResult['TASK'];

		if(is_array($this->arResult['CAN']['TASK']['ACTION']))
		{
			foreach($this->arResult['CAN']['TASK']['ACTION'] as $action => $flag)
			{
				$arResult['TASK']['META:ALLOWED_ACTIONS']['ACTION_'.$action] = $flag;
			}
		}
		$arResult['ALLOWED_ACTIONS'] = $arResult['TASK']['META:ALLOWED_ACTIONS'];
		$arResult['TASK']['META:ALLOWED_ACTIONS_CODES'] = $this->task->getAllowedTaskActions();

		//unset($arResult['CAN']);

		###############################################

		if (!$task["CHANGED_DATE"])
		{
			$task["CHANGED_DATE"] = $task["CREATED_DATE"];
			$task["CHANGED_BY"] = $task["CREATED_BY"];
		}

		// Temporary fix for http://jabber.bx/view.php?id=29741
		if (strpos($task['DESCRIPTION'], 'player/mediaplayer/player.swf') !== false)
		{
			$task['~DESCRIPTION'] = str_replace(
				' src="/bitrix/components/bitrix/player/mediaplayer/player.swf" ',
				' src="/bitrix/components/bitrix/player/mediaplayer/player" ',
				$task['~DESCRIPTION']
			);
			$task['DESCRIPTION'] = str_replace(
				' src=&quot;/bitrix/components/bitrix/player/mediaplayer/player.swf&quot; ',
				' src=&quot;/bitrix/components/bitrix/player/mediaplayer/player&quot; ',
				$task['DESCRIPTION']
			);
		}

		$arResult['USER_FIELDS'] = $arResult['AUX_DATA']['USER_FIELDS'];
		$this->arResult["SHOW_USER_FIELDS"] = false;
		if(is_array($this->arResult["USER_FIELDS"]) && !empty($this->arResult["USER_FIELDS"]))
		{
			foreach($this->arResult["USER_FIELDS"] as $field)
			{
				if ($field["VALUE"] !== false)
				{
					$this->arResult["SHOW_USER_FIELDS"] = true;
					break;
				}
			}
		}

		$arResult['COMPANY_WORKTIME'] = $arResult['AUX_DATA']['COMPANY_WORKTIME']['HOURS'];
		unset($arResult['AUX_DATA']);

		$this->getForumId();
		$this->getForumCommentFiles();
		$this->getTemplates();
		$this->getTaskDataRelatedTasks();
	}

	protected function getReferenceData()
	{
		parent::getReferenceData();

		$this->arResult['GROUPS'] = $this->arResult['DATA']['GROUP'];

		if ($this->arResult['TASK']["GROUP_ID"] && !empty($this->arResult['GROUPS'][$this->arResult['TASK']["GROUP_ID"]]))
		{
			$group = $this->arResult['GROUPS'][$this->arResult['TASK']["GROUP_ID"]];
			$this->arResult['TASK']["GROUP_NAME"] = htmlspecialcharsbx($group["NAME"]);
		}

		unset($this->arResult['DATA']['GROUP']);
	}

	// do nothing on reformat
	protected function formatData()
	{
	}

	protected function getTimemanFields()
	{
		parent::getTimemanFields();

		$this->arResult['DATA']['TASK']['META:IN_DAY_PLAN'] = $this->arResult['DATA']['TASK']['IN_DAY_PLAN'] ? 'Y' : 'N';
		$this->arResult['DATA']['TASK']['META:CAN_ADD_TO_DAY_PLAN'] = $this->arResult['CAN']['TASK']['ACTION']['ADD_TO_DAY_PLAN'] ? 'Y' : 'N';
	}

	protected function getTemplates()
	{
		$arResult =& $this->arResult;
		$task =& $this->arResult['TASK'];

		$res = CTaskTemplates::GetList(
			array("ID" => "DESC"),
			array("CREATED_BY" => $this->userId, 'BASE_TEMPLATE_ID' => false, '!TPARAM_TYPE' => CTaskTemplates::TYPE_FOR_NEW_USER),
			array('NAV_PARAMS' => array('nTopCount' => 10)),
			array(),	// misc params,
			array('ID', 'TITLE', 'TASK_ID', 'REPLICATE_PARAMS')		// $arSelect
		);

		$arResult["TEMPLATES"] = array();
		while($item = $res->GetNext())
		{
			$arResult["TEMPLATES"][] = $item;
		}

		$arLinkedTemplate = null;

		// Was task created from template?
		if ($task['FORKED_BY_TEMPLATE_ID'] > 0)
		{
			// Try to found this template in already fetched templates
			foreach ($arResult["TEMPLATES"] as &$arTemplate)
			{
				if ($arTemplate['ID'] == $task['FORKED_BY_TEMPLATE_ID'])
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
					array('ID' => $task['FORKED_BY_TEMPLATE_ID']),
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
				if ($arTemplate['TASK_ID'] == $this->arParams['TASK_ID'])
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
					array('TASK_ID' => $this->arParams['TASK_ID']),
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

			$task['TEMPLATE'] = $task['FORKED_BY_TEMPLATE'] = $arLinkedTemplate;
		}
	}

	protected function getFiles()
	{
		$task =& $this->arResult['TASK'];

		$task['FILES'] = $this->arResult['TASK']['~FILES'] = $this->task->getFiles();
		if ($task["FILES"])
		{
			$res = CFile::GetList(array(), array("@ID" => implode(",", $task["FILES"])));
			$task["FILES"] = array();
			while($file = $res->GetNext())
			{
				$task["FILES"][] = $file;
			}
		}
	}

	protected function getForumCommentFiles()
	{
		$this->arResult['TASK']["FORUM_FILES"] = array();
		if ($this->arResult['TASK']["FORUM_TOPIC_ID"])
		{
			$res = CForumFiles::GetList(array("ID"=>"ASC"), array("TOPIC_ID" => $this->arResult['TASK']["FORUM_TOPIC_ID"]));
			while($item = $res->GetNext())
			{
				$this->arResult['TASK']["FORUM_FILES"][] = $item;
			}
		}
	}

	protected function getForumId()
	{
		$arResult =& $this->arResult;
		$arParams =& $this->arParams;

		if($this->task !== null && $arResult["TASK"]['FORUM_ID'] >= 1)
		{
			$arResult['FORUM_ID'] = $arResult["TASK"]['FORUM_ID'];
		}
		elseif(intval($arParams['FORUM_ID']))
		{
			$arResult['FORUM_ID'] = intval($arParams['FORUM_ID']);
		}
		else
		{
			$arResult['FORUM_ID'] = CTasksTools::getForumIdForIntranet();
		}
	}

	protected function display()
	{
		if(!$this->displayFatals($this->arResult['ERROR']['FATAL'])) // no errors detected and shown therefore
		{
			if ($this->arResult["IS_IFRAME"])
			{
				ShowInFrame($this);
			}
			else
			{
				$this->IncludeComponentTemplate();
			}
		}
	}

	protected function displayFatals($fatals)
	{
		if(!is_array($fatals) || empty($fatals))
		{
			return false;
		}

		if($this->arResult["IS_IFRAME"])
		{
			ShowInFrame($this, true, array_shift($fatals['MESSAGE']));
		}
		else
		{
			foreach($fatals as $message)
			{
				ShowError($message['MESSAGE']);
			}
		}

		return true;
	}

	protected function processDelayedFunctions()
	{
		global $APPLICATION;

		$sTitle = $this->arResult["TASK"]['TITLE'] . ' (' . toLower(str_replace("#TASK_NUM#", $this->arResult["TASK"]["ID"], GetMessage("TASKS_TASK_NUM"))) . ')';

		if ($this->arParams["SET_TITLE"] == "Y")
		{
			$APPLICATION->SetTitle($sTitle);
		}

		if (!isset($this->arParams["SET_NAVCHAIN"]) || $this->arParams["SET_NAVCHAIN"] != "N")
		{
			if ($this->arResult['TASK_TYPE'] == "user")
			{
				$APPLICATION->AddChainItem(CUser::FormatName($this->arParams["NAME_TEMPLATE"], $this->arResult["USER"]), CComponentEngine::MakePathFromTemplate($this->arParams["~PATH_TO_USER_PROFILE"], array("user_id" => $this->arParams["USER_ID"])));
				$APPLICATION->AddChainItem($sTitle);
			}
			else
			{
				$APPLICATION->AddChainItem($this->arResult["GROUP"]["NAME"], CComponentEngine::MakePathFromTemplate($this->arParams["~PATH_TO_GROUP"], array("group_id" => $this->arParams["GROUP_ID"])));
				$APPLICATION->AddChainItem($sTitle);
			}
		}
	}

	protected static function getParameterAlias($name)
	{
		return $name == 'ID' ? 'TASK_ID' : $name;
	}

	protected static function getEscapedData()
	{
		return true;
	}

	protected static function cleanTaskData()
	{
	}

	protected static function getRequest()
	{
		return Context::getCurrent()->getRequest();
	}

	protected static function extractCSRF($request)
	{
		return $request['sessid'];
	}

	protected static function detectDispatchTrigger($request)
	{
		if(isset($request['ACTION']))
		{
			return $request['ACTION'];
		}
		elseif(isset($request['action']))
		{
			return $request['action'];
		}

		return false;
	}

	//($batch, Collection $errors)
	protected static function dispatch($action, Collection $errors, array $auxParams = array(), array $arParams = array())
	{
		global $APPLICATION;

		$request = 	$auxParams['REQUEST'];
		$taskID = 	$arParams['TASK_ID'];
		$type = 	$auxParams['QUERY_TYPE'];
		$oTask = 	$auxParams['ORIGIN_ARRESULT']['TASK_INSTANCE'];

		if($type == static::QUERY_TYPE_HIT)
		{
			$redirectTo = null;

			try
			{
				if ($action == "delete")
				{
					if ($request["back_url"])
						$redirectTo = $request["back_url"];
					else
						$redirectTo = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"]);
					
					$oTask->delete();
				}
				elseif ($action == "elapsed_add")
				{
					$redirectTo = $APPLICATION->GetCurPageParam(RandString(8), array("ACTION", "sessid"))."#elapsed";
					$minutes = ((int) $request['HOURS']) * 60 + (int) $request['MINUTES'];
					$cdate = $request['CREATED_DATE'];
					CTaskElapsedItem::add($oTask, array('MINUTES' => $minutes, 'COMMENT_TEXT' => trim($request["COMMENT_TEXT"]), 'CREATED_DATE' => $cdate));
				}
				elseif ($action === 'elapsed_update')
				{
					$seconds = ((int) $request['HOURS']) * 3600 + ((int) $request['MINUTES']) * 60;
					if (isset($request['SECONDS']) && ($request['SECONDS'] > 0))
						$seconds += (int) $request['SECONDS'];
					$cdate = $request['CREATED_DATE'];

					$redirectTo = $APPLICATION->GetCurPageParam("", array("ACTION", "sessid"))."#elapsed";
					$oElapsedItem = new CTaskElapsedItem($oTask, (int) $request['ELAPSED_ID']);
					$oElapsedItem->update(array(
						'SECONDS'      => $seconds,
						'COMMENT_TEXT' => trim($request["COMMENT_TEXT"]),
						'CREATED_DATE' => $cdate
					));
				}
				elseif ($action === 'elapsed_delete')
				{
					$redirectTo = $APPLICATION->GetCurPageParam("", array("ACTION", "sessid", "ELAPSED_ID"))."#elapsed";
					$oElapsedItem = new CTaskElapsedItem($oTask, (int) $request['ELAPSED_ID']);
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
							$arArgs = array($request['REASON']);
						elseif ($action === 'delegate')
							$arArgs = array($request['USER_ID']);

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

				$errors->add('TASKS_FAILED_TO_DO_ACTION', $strError);

				return;
			}

			if ($redirectTo)
			{
				LocalRedirect($redirectTo);
			}
		}
	}
}
