<?php

namespace Bitrix\Tasks\Replicator\Template\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Replicator\Template\Repository;
use Exception;

class SubTaskService
{
	public function __construct(
		private Repository $repository,
		private TaskObject $task,
		private array $fields,
		private int $userId
	)
	{
	}

	public function add(): Result
	{
		$result = new Result();
		$responsibleMemberIds = $this->repository->getTemplate()->getResponsibleMemberId();
		try
		{
			foreach ($responsibleMemberIds as $responsibleMemberId)
			{
				$this->fields['RESPONSIBLE_ID'] = $responsibleMemberId;
				$this->fields['PARENT_ID'] = $this->task->getId();
				$task = (new Task($this->userId))->add($this->fields);
				(new CheckListService($this->repository, $task, $this->userId))->copyToTask();
			}
		}
		catch (Exception $exception)
		{
			$result->addError(new Error($exception->getMessage()));
			return $result;
		}

		return $result;
	}
}