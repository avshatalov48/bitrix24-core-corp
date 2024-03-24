<?php

namespace Bitrix\Tasks\Replication\Task\Copy;

use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Copy\OrdinaryTaskManager;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Replication\ProducerInterface;
use Bitrix\Tasks\Replication\Replicator\CopyTaskReplicator;
use Bitrix\Tasks\Replication\RepositoryInterface;

class CopyTaskProducer implements ProducerInterface
{
	private RepositoryInterface $repository;
	private OrdinaryTaskManager $copier;
	private Result $currentResult;
	private ?DateTime $createdDate = null;

	private int $parentTaskId;

	public function __construct(RepositoryInterface $repository)
	{
		$this->repository = $repository;
		$this->init();
	}

	public function produceTask(): Result
	{
		$task = $this->repository->getEntity();
		$this->currentResult = $this->copier
			->setParentTaskId($this->parentTaskId)
			->startCopy();

		if (!$this->currentResult->isSuccess())
		{
			return $this->currentResult;
		}

		$copiedTaskId = $this->currentResult->getData()[$this->copier->getImplementerClass()][$task->getId()];

		$this->currentResult->setData([
			CopyTaskReplicator::getPayloadKey() => TaskRegistry::getInstance()->getObject($copiedTaskId)
		]);

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
		$task = $this->repository->getEntity();
		$this->copier = new OrdinaryTaskManager($task->getCreatedByMemberId(), [$task->getId()]);
	}
}