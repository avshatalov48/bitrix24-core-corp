<?php

namespace Bitrix\Tasks\Replication\Task\Regularity;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Copy\OrdinaryTaskManager;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Replication\ProducerInterface;
use Bitrix\Tasks\Replication\Task\Regularity\Time\Service\DeadlineRegularityService;
use Bitrix\Tasks\Replication\Replicator\RegularTaskReplicator;
use Bitrix\Tasks\Replication\RepositoryInterface;
use Exception;

class RegularTaskProducer implements ProducerInterface
{
	private RepositoryInterface $repository;
	private DeadlineRegularityService $deadlineService;
	private OrdinaryTaskManager $copier;
	private Result $currentResult;
	private ?DateTime $createdDate = null;

	private int $copiedTaskId;
	private int $parentTaskId;

	public function __construct(RepositoryInterface $repository)
	{
		$this->repository = $repository;
		$this->init();
	}

	public function produceTask(): Result
	{
		$task = $this->repository->getEntity();
		$this->currentResult = $this->copier->startCopy();

		if (!$this->currentResult->isSuccess())
		{
			return $this->currentResult;
		}

		$this->copiedTaskId = $this->currentResult->getData()[$this->copier->getImplementerClass()][$task->getId()];
		$this->currentResult->setData([RegularTaskReplicator::getPayloadKey() => $this->copiedTaskId]);

		$deadlineOffsetInDays = $task->getRegularDeadlineOffset();

		if ($deadlineOffsetInDays <= 0)
		{
			return $this->currentResult;
		}

		try
		{
			$copiedTask = TaskTable::getByPrimary($this->copiedTaskId)->fetchObject();
			$handler = new Task($copiedTask->getCreatedByMemberId());
			$handler->update($this->copiedTaskId, [
				'DEADLINE' => $this->deadlineService->getRecalculatedDeadline($task->getRegularFields()?->getStartTime())
			]);
		}
		catch (Exception $exception)
		{
			$this->currentResult->addError(Error::createFromThrowable($exception));
			return $this->currentResult;
		}


		return $this->currentResult;
	}

	public function setParentTaskId(int $taskId): static
	{
		$this->parentTaskId = $taskId;
		return $this;
	}

	public function setCreatedDate(DateTime $createdDate): static
	{
		$this->createdDate = $createdDate;
		return $this;
	}

	private function init(): void
	{
		$this->deadlineService = new DeadlineRegularityService($this->repository);
		$task = $this->repository->getEntity();
		$this->copier = new OrdinaryTaskManager($task->getCreatedByMemberId(), [$task->getId()]);
	}
}