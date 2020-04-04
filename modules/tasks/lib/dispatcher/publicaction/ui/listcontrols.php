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

use Bitrix\Tasks;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Manager;

final class ListControls extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	public function add($data, $parameters = array())
	{
		global $DB;

		if (!User::isAuthorized())
		{
			throw new Tasks\Exception("Authentication is required.");
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

		if (strlen($nameTemplate) > 0)
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

		$taskData = Tasks\Manager\Task::add(User::getId(), $fields);
		$taskItem = \CTaskItem::getInstance($taskData["DATA"]["ID"], User::getId());

		$task = $taskItem->getData();
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

	private function unserializeArray($key, $data)
	{
		$result = array();
		if (isset($data[$key]) && checkSerializedData($data[$key]))
		{
			$result = unserialize($data[$key]);
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

	public function toggleGroupByTasks()
	{
		$userId = User::getId();
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

	public function toggleGroupByGroups()
	{
		$userId = User::getId();
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