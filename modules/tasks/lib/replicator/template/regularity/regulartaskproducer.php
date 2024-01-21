<?php

namespace Bitrix\Tasks\Replicator\Template\Regularity;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Copy\RegularTaskManager;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Replicator\ProducerInterface;
use Bitrix\Tasks\Replicator\Template\Regularity\Time\Service\DeadlineRegularityService;
use Bitrix\Tasks\Replicator\Template\Replicators\RegularTaskReplicator;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;
use Exception;

class RegularTaskProducer implements ProducerInterface
{
	private RepositoryInterface $repository;
	private DeadlineRegularityService $deadlineService;
	private RegularTaskManager $copier;
	private Result $currentResult;
	private ?DateTime $createdDate = null;
	private int $copiedTaskId;

	public function __construct(RepositoryInterface $repository)
	{
		$this->repository = $repository;
		$this->init();
	}

	private function init(): void
	{
		$this->deadlineService = new DeadlineRegularityService($this->repository);
		$task = $this->repository->getEntity();
		$this->copier = new RegularTaskManager($task->getCreatedByMemberId(), [$task->getId()]);
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

		$deadlineOffsetInDays = (int)($task->getRegularDeadlineOffset());

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
	public function setCreatedDate(DateTime $createdDate): static
	{
		$this->createdDate = $createdDate;
		return $this;
	}
}