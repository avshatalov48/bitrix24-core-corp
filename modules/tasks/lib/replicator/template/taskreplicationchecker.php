<?php

namespace Bitrix\Tasks\Replicator\Template;

use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;
use Bitrix\Tasks\Replicator\Checker;
use Bitrix\Tasks\Replicator\Template\Repetition\Time\Service\ExecutionService;
use Bitrix\Tasks\Util\User;
use Exception;

class TaskReplicationChecker implements Checker
{
	private ExecutionService $executionService;

	public function __construct(private Repository $repository)
	{
		$this->executionService = new ExecutionService($repository);
	}

	public function stopReplicationByInvalidData(): bool
	{
		$template = $this->repository->getTemplate();
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

	private function isTaskByTemplateAlreadyProduced(): bool
	{
		try
		{
			$query = new TaskQuery();
			$query
				->setSelect(['ID'])
				->setWhere([
					'FORKED_BY_TEMPLATE_ID' => $this->repository->getTemplate()->getId(),
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