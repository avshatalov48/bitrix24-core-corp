<?php

namespace Bitrix\Tasks\Replication\Template\Common\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Replication\RepositoryInterface;
use Bitrix\Tasks\Replication\Template\Common\Service\ChecklistService;
use Exception;

class SubTaskService
{
	public function __construct(
		private RepositoryInterface $repository,
		private TaskObject $task,
		private array $fields,
		private int $userId
	)
	{
	}

	public function add(): Result
	{
		$result = new Result();
		$template = $this->repository->getEntity();
		$responsibleMemberIds = $template->getResponsibleMemberId();
		try
		{
			foreach ($responsibleMemberIds as $responsibleMemberId)
			{
				if ($responsibleMemberId === $template->getCreatedBy())
				{
					continue; // already created, skip
				}
				$this->fields['RESPONSIBLE_ID'] = $responsibleMemberId;
				$this->fields['PARENT_ID'] = $this->task->getId();
				$task = (new Task($this->userId))->add($this->fields);
				(new ChecklistService($this->repository, $task, $this->userId))->copyToTask();
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