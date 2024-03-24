<?php

namespace Bitrix\Tasks\Replication\Repository;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Replication\RepositoryInterface;

class TaskRepository implements RepositoryInterface
{
	private ?TaskObject $task = null;

	public function __construct(private int $taskId)
	{
	}

	public function getEntity(): null|TemplateObject|TaskObject
	{
		if (!is_null($this->task))
		{
			return $this->task;
		}

		try
		{
			$query = TaskTable::query();
			$query
				->setSelect(['*', 'REGULAR'])
				->where('ID', $this->taskId);

			$this->task = $query->exec()->fetchObject();
		}
		catch (SystemException)
		{
			return null;
		}


		return $this->task;
	}

	public function drop(): void
	{
		$this->task = null;
	}

	public function inject(TaskObject|TemplateObject $object): static
	{
		if ($object instanceof TaskObject)
		{
			$this->task = $object;
		}

		return $this;
	}
}