<?php

namespace Bitrix\Tasks\Replication\Template\Repetition;

use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;
use Bitrix\Tasks\Replication\CheckerInterface;
use Bitrix\Tasks\Replication\Replicator\RegularTemplateTaskReplicator;
use Bitrix\Tasks\Replication\RepositoryInterface;
use Bitrix\Tasks\Replication\Template\Repetition\Time\Service\ExecutionService;
use Bitrix\Tasks\Util\User;
use Exception;

class RegularTemplateTaskReplicationChecker implements CheckerInterface
{
	private ExecutionService $executionService;
	private int $userId = 0;

	public function __construct(private RepositoryInterface $repository)
	{
		$this->executionService = new ExecutionService($repository);
	}

	public function stopReplicationByInvalidData(): bool
	{
		$template = $this->repository->getEntity();
		if (is_null($template))
		{
			return true;
		}

		if (!$template->getReplicate())
		{
			return true;
		}

		if (!User::isActive($template->getCreatedBy()))
		{
			return true;
		}

		return false;
	}

	public function stopCurrentReplicationByPostpone(): bool
	{
		$priority = RegularTemplateTaskReplicator::isTimePriorityEnabled()
			? ExecutionService::PRIORITY_AGENT
			: ExecutionService::PRIORITY_TEMPLATE;

		$executionTime = $this->executionService->getTemplateCurrentExecutionTime($priority);
		$executionTimeTS = MakeTimeStamp($executionTime);
		$currentServerTimeTS = time();

		if (
			$executionTime
			&& (
				$this->isTaskByTemplateAlreadyProduced($priority)
				|| $currentServerTimeTS < $executionTimeTS
			)
		)
		{
			return true;
		}

		return false;
	}

	public function setUserId(int $userId): static
	{
		$this->userId = $userId;
		return $this;
	}

	private function isTaskByTemplateAlreadyProduced(int $priority = ExecutionService::PRIORITY_TEMPLATE): bool
	{
		try
		{
			$query = new TaskQuery();
			$query
				->setSelect(['ID'])
				->setWhere([
					'FORKED_BY_TEMPLATE_ID' => $this->repository->getEntity()->getId(),
					'CREATED_DATE' => $this->executionService->getTemplateCurrentExecutionTime($priority),
				])
				->setLimit(1)
				->skipAccessCheck();

			$list = new TaskList();
			$tasks = $list->getList($query);

			return !empty($tasks);
		}
		catch (Exception)
		{
			return false;
		}
	}
}