<?php

namespace Bitrix\Tasks\Replication\Template\Repetition;

use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;
use Bitrix\Tasks\Replication\CheckerInterface;
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

		// todo: Gannochenko use first admin if CREATED_BY does not exists. idk why

		// if (User::getAdminId())
		if (!User::isActive($template->getCreatedBy()))
		{
			return true;
		}

		return false;
	}

	public function stopCurrentReplicationByPostpone(): bool
	{
		$executionTime = $this->executionService->getTemplateCurrentExecutionTime();
		$executionTimeTS = MakeTimeStamp($executionTime);
		$currentServerTimeTS = time();
		if (
			$executionTime
			&& (
				$this->isTaskByTemplateAlreadyProduced()
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

	private function isTaskByTemplateAlreadyProduced(): bool
	{
		try
		{
			$query = new TaskQuery();
			$query
				->setSelect(['ID'])
				->setWhere([
					           'FORKED_BY_TEMPLATE_ID' => $this->repository->getEntity()->getId(),
					           'CREATED_DATE' => $this->executionService->getTemplateCurrentExecutionTime(),
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