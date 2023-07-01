<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline\Tasks\Controller;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\CRM\Fields\Mapper;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;

class TaskRepository implements BackGroundJob
{
	private int $taskId;
	private int $userId;
	private Controller $controller;
	private Application $application;
	private ?TaskObject $task = null;
	private ?Bindings $bindings = null;

	public function __construct(int $taskId, int $userId)
	{
		if (!Loader::includeModule('crm'))
		{
			return;
		}

		$this->taskId = $taskId;
		$this->userId = $userId;
		$this->controller = Controller::getInstance();
		$this->application = Application::getInstance();
	}

	/**
	 * @uses Controller::onTaskAdded
	 * @uses Controller::onTaskUpdated
	 * @uses Controller::onTaskDeleted
	 * @uses Controller::onTaskExpired
	 * @uses Controller::onTaskViewed
	 * @uses Controller::onTaskCompleted
	 * @uses Controller::onTaskDescriptionChanged
	 * @uses Controller::onTaskStatusChanged
	 * @uses Controller::onTaskDeadLineChanged
	 * @uses Controller::onTaskPingSent
	 * @uses Controller::onTaskChecklistAdded
	 * @uses Controller::onTaskResultAdded
	 * @uses Controller::onTaskAccompliceAdded
	 * @uses Controller::onTaskAuditorAdded
	 * @uses Controller::onTaskBindingsUpdated
	 * @uses Controller::onTaskCommentAdded
	 * @uses Controller::onTaskAllCommentViewed
	 * @uses Controller::onTaskDisapproved
	 * @uses Controller::onTaskFilesUpdated
	 * @uses Controller::onTaskGroupChanged
	 * @uses Controller::onTaskRenew
	 * @uses Controller::onTaskResponsibleChanged
	 * @uses Controller::onTaskTitleUpdated
	 * @uses Controller::onTaskCommentDeleted
	 */
	public function addToBackgroundJobs(array $payload, string $endpoint = '', int $priority = 0): void
	{
		if (!$endpoint)
		{
			return;
		}
		if (isset($payload['IMMEDIATELY']) && $payload['IMMEDIATELY'] === true)
		{
			$this->controller->$endpoint($this->getBindings(), $payload);
		}
		else
		{
			$this->application->addBackgroundJob(
				[$this->controller, $endpoint],
				[$this->getBindings(), $payload],
				$priority
			);
		}
	}

	public function getTask(): ?TaskObject
	{
		if ($this->task)
		{
			return $this->task;
		}

		$select = [
			'ID',
			'TITLE',
			'DESCRIPTION',
			'UF_CRM_TASK',
			'STATUS',
			'SCENARIO',
			'DEADLINE',
			'RESPONSIBLE_ID',
			'CREATED_BY',
			'UF_TASK_WEBDAV_FILES',
			'GROUP_ID',
			'START_DATE_PLAN',
			'END_DATE_PLAN',
		];

		$query = (new TaskQuery($this->userId))
			->setBehalfUser($this->userId)
			->setSelect($select)
			->setWhere([
				'=ID' => $this->taskId,
			])
			->skipAccessCheck()
			->setLimit(1);

		$list = new TaskList();
		$tasks = $list->getList($query);
		$task = $tasks[0] ?? null;
		if (!is_null($task))
		{
			$this->task = new TaskObject($task);
			$this->task->fillMemberList();
		}
		else
		{
			$this->task = null;
		}

		return $this->task;
	}

	public function getBindings(): Bindings
	{
		if (!is_null($this->bindings))
		{
			return $this->bindings;
		}

		if (is_null($this->getTask()))
		{
			$this->bindings = new Bindings();
			return $this->bindings;
		}

		if (empty($this->getTask()->getCrmFields()))
		{
			$this->bindings = new Bindings();
			return $this->bindings;
		}

		$bindings = [];
		$crmFieldsCollection = (new Mapper())->map($this->getTask()->getCrmFields());
		foreach ($crmFieldsCollection as $crmField)
		{
			try
			{
				$bindings[] = new ItemIdentifier($crmField->getTypeId(), $crmField->getId());
			}
			catch (ArgumentException $exception)
			{
				continue;
			}
		}

		$this->bindings = new Bindings(...$bindings);

		return $this->bindings;
	}
}