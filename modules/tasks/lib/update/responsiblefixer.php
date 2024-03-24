<?php

namespace Bitrix\Tasks\Update;

use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Task\MemberCollection;
use Bitrix\Tasks\Internals\TaskCollection;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\TaskTable;
use Exception;

final class ResponsibleFixer extends Stepper
{
	private const LIMIT = 100;

	protected static $moduleId = 'tasks';

	private int $lastId;
	private TaskCollection $tasks;

	public function execute(array &$option): bool
	{
		$this
			->setLastId($option['lastId'] ?? 0)
			->fillBrokenTasks();

		if ($this->tasks->isEmpty())
		{
			return self::FINISH_EXECUTION;
		}

		$this
			->convertBrokenTasks()
			->updateLastId()
			->setOptions($option);

		return self::CONTINUE_EXECUTION;
	}

	private function fillBrokenTasks(): self
	{
		$this->tasks = new TaskCollection();
		try
		{
			$query = TaskTable::query();
			$query
				->setSelect(['ID', 'RESPONSIBLE_ID', 'CREATED_BY'])
				->where('RESPONSIBLE_ID', 0)
				->where('ID', '>', $this->lastId)
				->setLimit(self::LIMIT);
			$this->tasks = $query->exec()->fetchCollection();
		}
		catch (Exception $exception)
		{
			LogFacade::logThrowable($exception);
		}

		return $this;
	}

	private function convertBrokenTasks(): self
	{
		$members = new MemberCollection();
		foreach ($this->tasks as $task)
		{
			$task->setResponsibleId($task->getCreatedBy());
			$members->addResponsible($task->getCreatedBy(), $task->getId());
		}

		$result = $this->tasks->save(true);
		if (!$result->isSuccess())
		{
			LogFacade::logErrors($result->getErrorCollection());
		}

		$result = $members->save(true);
		if (!$result->isSuccess())
		{
			LogFacade::logErrors($result->getErrorCollection());
		}

		return $this;
	}

	private function setLastId(int $id = 0): self
	{
		$this->lastId = $id;
		return $this;
	}

	private function updateLastId(): self
	{
		$this->lastId = max(array_map(fn (TaskObject $task): int => $task->getId(), iterator_to_array($this->tasks)));
		return $this;
	}

	private function setOptions(array &$options): self
	{
		$options['lastId'] = $this->lastId;
		return $this;
	}
}