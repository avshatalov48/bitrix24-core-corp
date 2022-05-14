<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Tasks\Control\Exception\TaskNotFoundException;
use Bitrix\Tasks\Internals\TaskTable;

trait BaseControlTrait
{
	private $userId;
	private $taskId;

	/* @var \Bitrix\Tasks\Internals\TaskObject $task */
	private $task;

	public function __construct(int $userId, int $taskId)
	{
		$this->userId = $userId;
		$this->taskId = $taskId;
	}

	/**
	 * @return void
	 * @throws TaskNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function loadTask(): void
	{
		if ($this->task)
		{
			return;
		}

		$this->task = TaskTable::getByPrimary($this->taskId)->fetchObject();
		if (!$this->task)
		{
			throw new TaskNotFoundException();
		}
		$this->task->fillMemberList();
	}
}