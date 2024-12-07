<?php

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Errorable;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Action\Filter\BooleanFilter;
use Bitrix\Tasks\Manager\Task;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Slider\Path\PathMaker;
use Bitrix\Tasks\Slider\Path\TaskPathMaker;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\Restriction;
use Bitrix\Tasks\Util\User;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksQuickFormComponent extends TasksBaseComponent implements Errorable, Controllerable
{
	protected Collection $errorCollection;

	public function configureActions(): array
	{
		if (!Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'addTask' => [
				'+prefilters' => [
					new BooleanFilter(),
				],
			],
		];
	}

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->init();
	}

	private function init(): void
	{
		if (!Loader::includeModule('tasks'))
		{
			return;
		}

		$this->userId = User::getId();
		$this->errorCollection = new Collection();
	}

	public function getErrorByCode($code)
	{
	}

	public function getErrors()
	{
		if (!empty($this->componentId))
		{
			return parent::getErrors();
		}
		return $this->errorCollection->toArray();
	}

	public function addTaskAction($data): ?array
	{
		global $DB;

		if (!Loader::includeModule('tasks'))
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
		elseif ($userEmail === "")
		{
			$responsibleId = $this->userId;
		}

		$deadline = "";
		if (
			isset($data["deadline"])
			&& $DB->FormatDate($data["deadline"], CSite::GetDateFormat())
		)
		{
			$deadline = $data["deadline"];
		}

		$description = isset($data["description"]) ? trim($data["description"]) : "";
		$project = isset($data["project"]) ? intval($data["project"]) : 0;
		$nameTemplate = isset($data["nameTemplate"]) ? trim($data["nameTemplate"]) : "";
		$ganttMode = isset($data["ganttMode"])
			&& ($data["ganttMode"] === true
				|| $data["ganttMode"] === "1"
				|| $data["ganttMode"] === "true");

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
			$nameTemplate = CSite::GetNameFormat(false);
		}

		$fields = [
			"TITLE" => $title,
			"DESCRIPTION" => $description,
			"SE_RESPONSIBLE" => [
				$userEmail !== ""
					? [
					"EMAIL" => $userEmail,
					"NAME" => $userName,
					"LAST_NAME" => $userLastName,
				]
					: [
					"ID" => $responsibleId,
				],
			],
			"DEADLINE" => $deadline,
			"SITE_ID" => $data["siteId"],
			"GROUP_ID" => $project,
			"NAME_TEMPLATE" => $nameTemplate,
			"DESCRIPTION_IN_BBCODE" => "Y",
		];

		$task = TaskModel::createNew($fields['GROUP_ID']);
		$task->setMembers([
			RoleDictionary::ROLE_RESPONSIBLE => [
				$fields['SE_RESPONSIBLE'][0]['ID']
				?? $fields['SE_RESPONSIBLE'][0]['EMAIL'],
			],
		]);

		$result['currentUser'] = [
			'id' => $this->userId,
			'fullName' => CurrentUser::get()->getFormattedName(),
		];

		if (
			!(new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_SAVE,
				TaskModel::createNew(), $task)
		)
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			$taskData = Task::add($this->userId, $fields);
		}
		catch (TasksException $exception)
		{
			$this->errorCollection->add('UNEXPECTED_ERROR', $exception->getFirstErrorMessage());
			return $result;
		}

		$taskItem = \CTaskItem::getInstance($taskData["DATA"]["ID"], $this->userId);

		try
		{
			$task = $taskItem->getData();
		}
		catch (\TasksException $e)
		{
			$this->errorCollection->add('UNEXPECTED_ERROR', '');
			return $result;
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
			[
				"USER" => [$task["RESPONSIBLE_ID"]],
				"SGROUP" => [$task["GROUP_ID"]],
			]
		);

		$taskId = $taskItem->getId();

		$arPaths = [
			"PATH_TO_TASKS_TASK" => isset($data["pathToTask"]) ? trim($data["pathToTask"]) : "",
		];

		$getListParameters = $this->unserializeArray("getListParams", $data);
		$context = $task['GROUP_ID'] > 0 ? PathMaker::GROUP_CONTEXT : PathMaker::PERSONAL_CONTEXT;
		$ownerId = $task['GROUP_ID'] > 0 ? $task['GROUP_ID'] : $this->userId;

		$result = [];
		$result["taskRaw"] = $task;
		$result["taskId"] = $task["ID"];
		$result["taskPath"] = (new TaskPathMaker($taskId, PathMaker::DEFAULT_ACTION, $ownerId,
			$context))->makeEntityPath();

		$result["position"] = $this->getTaskPosition($taskId, $getListParameters);

		if ($ganttMode)
		{
			$result["task"] = $this->getJson($task, $arPaths, $nameTemplate);
		}

		return $result;
	}

	protected function checkParameters()
	{
		parent::checkParameters();

		$arParams =& $this->arParams;

		static::tryParseStringParameter($arParams["NAME_TEMPLATE"], CSite::GetNameFormat(false));
	}

	protected function getData()
	{
		parent::getData();

		$this->arResult["DESTINATION"] = SocialNetwork::getLogDestination('TASKS', [
			'USE_PROJECTS' => 'Y',
		]);
		$this->arResult["GROUP"] = \CSocNetGroup::getByID($this->arParams["GROUP_ID"]);

		$canAddMailUsers = (
			ModuleManager::isModuleInstalled("mail")
			&& ModuleManager::isModuleInstalled("intranet")
			&& (
				!Loader::includeModule("bitrix24")
				|| \CBitrix24::isEmailConfirmed()
			)
		);

		$this->arResult["CAN"] = [
			"addMailUsers" => $canAddMailUsers,
			"manageTask" => Restriction::canManageTask(),
		];

		$user = \CUser::getByID($this->arParams["USER_ID"]);
		$this->arResult["USER"] = $user->fetch();
	}

	private function addForbiddenError(): void
	{
		$this->errorCollection->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
	}

	private function unserializeArray($key, $data)
	{
		$result = [];
		if (isset($data[$key]) && checkSerializedData($data[$key]))
		{
			$result = unserialize($data[$key], ['allowed_classes' => false]);
			if (!is_array($result))
			{
				$result = [];
			}
		}

		return $result;
	}

	private function getTaskPosition(int $taskId, array $getListParameters): array
	{
		$list = Task::getList($this->userId, $getListParameters, ["PUBLIC_MODE" => true]);
		$items = $list["DATA"];

		$result = [
			"found" => false,
			"prevTaskId" => 0,
			"nextTaskId" => 0,
		];

		foreach ($items as $i => $item)
		{
			$id = (int)$item["ID"];
			if ($id === $taskId)
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
		tasksRenderJSON($task, 0, $arPaths, false, true, false, $nameTemplate);

		return ob_get_clean();
	}
}