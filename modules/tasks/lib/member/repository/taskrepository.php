<?php

namespace Bitrix\Tasks\Member\Repository;

use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\EO_Member_Collection;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Member\RepositoryInterface;
use Exception;

class TaskRepository implements RepositoryInterface
{
	private ?TaskObject $task = null;

	public function __construct(private int $taskId)
	{
	}

	public function getEntity(): TaskObject|null
	{
		if (!is_null($this->task))
		{
			return $this->task;
		}

		try
		{
			$this->task = TaskRegistry::getInstance()->getObject($this->taskId);
		}
		catch (Exception)
		{
			return null;
		}

		if (is_null($this->task))
		{
			return null;
		}

		$this->task->fillMemberList();

		return $this->task;
	}

	public function getMembers(): EO_Member_Collection
	{
		return $this->getEntity()->getMemberList();
	}

	public function getType(): string
	{
		return 'Task';
	}
}