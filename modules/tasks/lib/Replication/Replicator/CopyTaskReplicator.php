<?php

namespace Bitrix\Tasks\Replication\Replicator;

use Bitrix\Tasks\Replication\AbstractReplicator;
use Bitrix\Tasks\Replication\CheckerInterface;
use Bitrix\Tasks\Replication\ProducerInterface;
use Bitrix\Tasks\Replication\RepeaterInterface;
use Bitrix\Tasks\Replication\ReplicationResult;
use Bitrix\Tasks\Replication\Task\Copy\CopyTaskChecker;
use Bitrix\Tasks\Replication\Task\Copy\CopyTaskProducer;
use Bitrix\Tasks\Replication\Fake\FakeRepeater;
use Bitrix\Tasks\Replication\Repository\TaskRepository;
use Bitrix\Tasks\Replication\RepositoryInterface;

/**
 * Not currently in use.
 * We need to troubleshoot copying issues
 * with various scenarios related to the parent task
 */
class CopyTaskReplicator extends AbstractReplicator
{
	private const PAYLOAD_KEY = 'task';

	private int $parentTaskId = 0;

	public static function isEnabled(): bool
	{
		return true;
	}

	public static function getPayloadKey(): string
	{
		return static::PAYLOAD_KEY;
	}

	public function setParentTaskId(int $taskId): static
	{
		$this->parentTaskId = $taskId;
		return $this;
	}

	protected function getProducer(): ProducerInterface
	{
		return new CopyTaskProducer($this->getRepository());
	}

	protected function getRepeater(): RepeaterInterface
	{
		return new FakeRepeater($this->getRepository());
	}

	protected function getChecker(): CheckerInterface
	{
		return new CopyTaskChecker($this->getRepository());
	}

	protected function getRepository(): RepositoryInterface
	{
		return new TaskRepository($this->entityId);
	}

	protected function replicateImplementation(int $entityId, bool $force = false): ReplicationResult
	{
		$this->currentResults = [];
		$this->replicationResult = new ReplicationResult($this);
		if (!static::isEnabled())
		{
			return $this->replicationResult;
		}

		$this->init($entityId);

		$this->checker->setUserId($this->userId);

		if ($this->checker->stopReplicationByInvalidData())
		{
			return $this->replicationResult;
		}

		$this->currentResults[] = $this->producer->setParentTaskId($this->parentTaskId)->produceTask();

		return $this->replicationResult
			->merge(...$this->currentResults)
			->writeToLog();
	}
}