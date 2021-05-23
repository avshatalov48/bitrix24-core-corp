<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Ui;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Manager;

Loc::loadMessages(__FILE__);

final class ListControls extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	public function add($data, $parameters = array())
	{
		global $DB;

		if (!User::isAuthorized())
		{
			throw new Tasks\Exception(Loc::getMessage('TASKS_LISTCONTROLS_AUTH_REQUIRED'));
		}

		$title = isset($data["title"]) ? trim($data["title"]) : "";

		$userEmail = isset($data["userEmail"]) ? trim($data["userEmail"]) : "";
		$userName = isset($data["userName"]) ? trim($data["userName"]) : "";
		$userLastName = isset($data["userLastName"]) ? trim($data["userLastName"]) : "";

		$responsibleId = 0;
		if (isset($data["responsibleId"]))
		{
			$responsibleId = intval($data["responsibleId"]);
		}
		else if ($userEmail === "")
		{
			$responsibleId = User::getId();
		}

		$deadline =
			isset($data["deadline"]) && $DB->FormatDate($data["deadline"], \CSite::GetDateFormat("FULL"))
			? $data["deadline"]
			: ""
		;
		$description = isset($data["description"]) ? trim($data["description"]) : "";
		$project = isset($data["project"]) ? intval($data["project"]) : 0;
		$nameTemplate = isset($data["nameTemplate"]) ? trim($data["nameTemplate"]) : "";
		$ganttMode = isset($data["ganttMode"]) && ($data["ganttMode"] === true || $data["ganttMode"] === "1");

		if ($nameTemplate <> '')
		{
			preg_match_all(
				"/(#NAME#)|(#NOBR#)|(#\\/NOBR#)|(#LAST_NAME#)|(#SECOND_NAME#)|(#NAME_SHORT#)|(#SECOND_NAME_SHORT#)|\\s|\\,/",
				$nameTemplate,
				$matches
			);
			$nameTemplate = implode("", $matches[0]);
		}
		else
		{
			$nameTemplate = \CSite::GetNameFormat(false);
		}

		$fields = array(
			"TITLE" => $title,
			"DESCRIPTION" => $description,
			"SE_RESPONSIBLE" => array(
				$userEmail !== ""
					? array(
						"EMAIL" => $userEmail,
						"NAME" => $userName,
						"LAST_NAME" => $userLastName
					)
					: array(
						"ID" => $responsibleId
					)
			),
			"DEADLINE" => $deadline,
			"SITE_ID" => $data["siteId"],
			"GROUP_ID" => $project,
			"NAME_TEMPLATE" => $nameTemplate,
			"DESCRIPTION_IN_BBCODE" => "Y"
		);

		if (!$this->checkRights($fields))
		{
			throw new Tasks\Exception(Loc::getMessage('TASKS_LISTCONTROLS_ACCESS_DENIED'));
		}

		$taskData = Tasks\Manager\Task::add(User::getId(), $fields);
		$taskItem = \CTaskItem::getInstance($taskData["DATA"]["ID"], User::getId());

		try
		{
			$task = $taskItem->getData();
		}
		catch (\TasksException $e)
		{
			throw new Tasks\Exception();
		}

		$task["GROUP_NAME"] = "";
		if ($task["GROUP_ID"])
		{
			$socGroup = \CSocNetGroup::GetByID($task["GROUP_ID"]);
			if ($socGroup)
			{
				$task["GROUP_NAME"] = $socGroup["NAME"];
			}
		}

		Integration\SocialNetwork::setLogDestinationLast(
			array(
				"USER" => array($task["RESPONSIBLE_ID"]),
				"SGROUP" => array($task["GROUP_ID"])
			)
		);

		$taskId = $taskItem->getId();

		$arPaths = array(
			"PATH_TO_TASKS_TASK" => isset($data["pathToTask"]) ? trim($data["pathToTask"]) : "",
		);

		$getListParameters = $this->unserializeArray("getListParams", $data);

		$result = array();
		$result["taskRaw"] = $task;
		$result["taskId"] = $task["ID"];
		$result["taskPath"] = \CComponentEngine::MakePathFromTemplate(
			$arPaths["PATH_TO_TASKS_TASK"],
			array(
				"task_id" => $task["ID"],
				"group_id" => $project,
				"user_id" => User::getId(),
				"action" => "view"
			)
		);

		$result["position"] = $this->getTaskPosition($taskId, $getListParameters);

		if ($ganttMode)
		{
			$result["task"] = $this->getJson($task, $arPaths, $nameTemplate);
		}

		return $result;
	}

	private function checkRights($fields)
	{
		$task = Tasks\Access\Model\TaskModel::createNew($fields['GROUP_ID']);
		$task->setMembers([
			RoleDictionary::ROLE_RESPONSIBLE => [
					isset($fields['SE_RESPONSIBLE'][0]['ID'])
					? $fields['SE_RESPONSIBLE'][0]['ID']
					: $fields['SE_RESPONSIBLE'][0]['EMAIL']
				]
		]);

		return (new Tasks\Access\TaskAccessController(User::getId()))->check(Tasks\Access\ActionDictionary::ACTION_TASK_SAVE, Tasks\Access\Model\TaskModel::createNew(), $task);
	}

	private function unserializeArray($key, $data)
	{
		$result = array();
		if (isset($data[$key]) && checkSerializedData($data[$key]))
		{
			$result = unserialize($data[$key], ['allowed_classes' => false]);
			if (!is_array($result))
			{
				$result = array();
			}
		}

		return $result;
	}

	private function getTaskPosition($taskId, array $getListParameters)
	{
		$list = Manager\Task::getList(User::getId(), $getListParameters, array("PUBLIC_MODE" => true));
		$items = $list["DATA"];

		$result = array(
			"found" => false,
			"prevTaskId" => 0,
			"nextTaskId" => 0
		);

		foreach ($items as $i => $item)
		{
			$id = $item["ID"];
			if ($id == $taskId)
			{
				$result["found"] = true;
				if (isset($items[$i + 1]))
				{
					$result["nextTaskId"] = $items[$i + 1]["ID"];
				}

				break;
			}

			$result["prevTaskId"] = $id;
		}

		return $result;
	}

	private function getJson($task, $arPaths, $nameTemplate)
	{
		ob_start();
		tasksRenderJSON($task, 0, $arPaths, false, true, false, $nameTemplate, array());
		$jsonString = ob_get_clean();

		return $jsonString;
	}

	public function toggleGroupByTasks($userId = null)
	{
		if (!User::isAuthorized())
		{
			throw new Tasks\Exception("Authentication is required.");
		}

		if (!is_null($userId))
		{
			$userId = (int) $userId;
		}

		if (!$userId)
		{
			$userId = User::getId();
		}

		$instance = \CTaskListState::getInstance($userId);
		$state = $instance->getState();
		$submodes = $state['SUBMODES'];
		$groupBySubTasks = $submodes['VIEW_SUBMODE_WITH_SUBTASKS']['SELECTED'] == 'Y';

		if ($groupBySubTasks)
		{
			$instance->switchOffSubmode(\CTaskListState::VIEW_SUBMODE_WITH_SUBTASKS);
		}
		else
		{
			$instance->switchOnSubmode(\CTaskListState::VIEW_SUBMODE_WITH_SUBTASKS);
		}
		$instance->saveState();

		// test
		$state = $instance->getState();
		$groupBySubTasks = $state['SUBMODES']['VIEW_SUBMODE_WITH_SUBTASKS']['SELECTED'] == 'Y';

		return array('RESULT' => $groupBySubTasks);
	}

	public function toggleGroupByGroups($userId = null)
	{
		if (!User::isAuthorized())
		{
			throw new Tasks\Exception("Authentication is required.");
		}

		if (!is_null($userId))
		{
			$userId = (int) $userId;
		}

		if (!$userId)
		{
			$userId = User::getId();
		}

		$instance = \CTaskListState::getInstance($userId);
		$state = $instance->getState();
		$submodes = $state['SUBMODES'];
		$groupByGroups = $submodes['VIEW_SUBMODE_WITH_GROUPS']['SELECTED'] == 'Y';

		if ($groupByGroups)
		{
			$instance->switchOffSubmode(\CTaskListState::VIEW_SUBMODE_WITH_GROUPS);
		}
		else
		{
			$instance->switchOnSubmode(\CTaskListState::VIEW_SUBMODE_WITH_GROUPS);
		}
		$instance->saveState();

		// test
		$state = $instance->getState();
		$groupByGroups = $state['SUBMODES']['VIEW_SUBMODE_WITH_GROUPS']['SELECTED'] == 'Y';

		return array('RESULT' => $groupByGroups);
	}
}