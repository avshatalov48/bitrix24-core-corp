<?php

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Manager\Task;
use Bitrix\Tasks\Integration\SocialNetwork;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksQuickFormComponent extends TasksBaseComponent
	implements \Bitrix\Main\Errorable, \Bitrix\Main\Engine\Contract\Controllerable
{
	protected $errorCollection;

	public function configureActions()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'addTask' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
		];
	}

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->init();
	}

	protected function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$this->setUserId();
		$this->errorCollection = new \Bitrix\Tasks\Util\Error\Collection();
	}

	protected function setUserId()
	{
		$this->userId = (int) \Bitrix\Tasks\Util\User::getId();
	}

	public function getErrorByCode($code)
	{
		// TODO: Implement getErrorByCode() method.
	}

	public function getErrors()
	{
		if (!empty($this->componentId))
		{
			return parent::getErrors();
		}
		return $this->errorCollection->toArray();
	}

	public function addTaskAction($data)
	{
		global $DB;

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
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
			$responsibleId = $this->userId;
		}

		$deadline = "";
		if (
			isset($data["deadline"])
			&& $DB->FormatDate($data["deadline"], \CSite::GetDateFormat("FULL"))
		)
		{
			$deadline = $data["deadline"];
		}

		$description = isset($data["description"]) ? trim($data["description"]) : "";
		$project = isset($data["project"]) ? intval($data["project"]) : 0;
		$nameTemplate = isset($data["nameTemplate"]) ? trim($data["nameTemplate"]) : "";
		$ganttMode = isset($data["ganttMode"]) && ($data["ganttMode"] === true || $data["ganttMode"] === "1" || $data["ganttMode"] === "true");

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

		$task = TaskModel::createNew($fields['GROUP_ID']);
		$task->setMembers([
			RoleDictionary::ROLE_RESPONSIBLE => [
				$fields['SE_RESPONSIBLE'][0]['ID']
				?? $fields['SE_RESPONSIBLE'][0]['EMAIL']
			]
		]);

		if (!(new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_SAVE, TaskModel::createNew(), $task))
		{
			$this->addForbiddenError();
			return [];
		}

		$taskData = Task::add($this->userId, $fields);
		$taskItem = \CTaskItem::getInstance($taskData["DATA"]["ID"], $this->userId);

		try
		{
			$task = $taskItem->getData();
		}
		catch (\TasksException $e)
		{
			$this->errorCollection->add('UNEXPECTED_ERROR', '');
			return [];
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

		SocialNetwork::setLogDestinationLast(
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
				"user_id" => $this->userId,
				"action" => "view"
			)
		);

		$result["position"] = $this->getTaskPosition($taskId, $getListParameters);

		if ($ganttMode)
		{
			$result["task"] = $this->getJson($task, $arPaths, $nameTemplate);
		}
		$result['currentUser'] = [
			'id' => $this->userId,
			'fullName' => CurrentUser::get()->getFormattedName(),
		];

		return $result;
	}

	protected function checkParameters()
	{
		parent::checkParameters();

		$arParams =& $this->arParams;

		static::tryParseStringParameter($arParams["NAME_TEMPLATE"], \CSite::GetNameFormat(false));
	}

	protected function getData()
	{
		parent::getData();

		$this->arResult["DESTINATION"] = SocialNetwork::getLogDestination('TASKS', array(
			'USE_PROJECTS' => 'Y'
		));
		$this->arResult["GROUP"] = \CSocNetGroup::getByID($this->arParams["GROUP_ID"]);

		$canAddMailUsers = (
			\Bitrix\Main\ModuleManager::isModuleInstalled("mail") &&
			\Bitrix\Main\ModuleManager::isModuleInstalled("intranet") &&
			(
				!\Bitrix\Main\Loader::includeModule("bitrix24")
				|| \CBitrix24::isEmailConfirmed()
			)
		);

		$this->arResult["CAN"] = array(
			"addMailUsers" => $canAddMailUsers,
			"manageTask" => \Bitrix\Tasks\Util\Restriction::canManageTask()
		);


		$user = \CUser::getByID($this->arParams["USER_ID"]);
		$this->arResult["USER"] = $user->fetch();
	}

	private function addForbiddenError()
	{
		$this->errorCollection->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
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
		$list = Task::getList($this->userId, $getListParameters, array("PUBLIC_MODE" => true));
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
}