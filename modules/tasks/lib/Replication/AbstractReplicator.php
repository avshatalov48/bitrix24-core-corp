<?php

namespace Bitrix\Tasks\Replication;

use Bitrix\Main\Result;
use Bitrix\Tasks\Internals\Log\LogFacade;
use CTimeZone;
use Throwable;

/**
 * Replicate an entity based on
 * data from another entity.
 */
abstract class AbstractReplicator
{
	/** @var Result[] $currentResults */
	protected array $currentResults = [];
	protected int $entityId;
	protected int $userId = 0;

	protected ReplicationResult $replicationResult;
	protected ProducerInterface $producer;
	protected RepeaterInterface $repeater;
	protected CheckerInterface $checker;

	public function __construct(int $userId = 0)
	{
		$this->userId = $userId;
	}

	/**
	 * Enable or disable replication.
	 */
	abstract public static function isEnabled(): bool;

	/**
	 * Returns the key by which it is convenient
	 * to obtain the value of the replication result.
	 *
	 * @see ReplicationResult
	 */
	abstract public static function getPayloadKey(): string;

	/**
	 * Returns the class that responds
	 * about how a new entity will be created.
	 */
	abstract protected function getProducer(): ProducerInterface;

	/**
	 * Returns the class that responds
	 * how exactly to repeat replication
	 * entities (add an agent, create another entity, etc.).
	 */
	abstract protected function getRepeater(): RepeaterInterface;

	/**
	 * Returns the class defining
	 * is it possible to repeat an object.
	 */
	abstract protected function getChecker(): CheckerInterface;

	/**
	 * Returns the class from which data
	 * will be taken during the replication process.
	 */
	abstract protected function getRepository(): RepositoryInterface;

	/**
	 * Replicates an entity.
	 */
	public function replicate(int $entityId, bool $force = false): ReplicationResult
	{
		$this->onBeforeReplicate($force);
		$result = new ReplicationResult($this);

		try
		{
			$result = $this->replicateImplementation($entityId, $force);
		}
		catch (Throwable $throwable)
		{
			$this->logUnexpectedError($throwable);
			$result->addThrowable($throwable);
		}
		finally
		{
			$this->onAfterReplicate($force);
		}

		return $result;
	}

	abstract protected function replicateImplementation(int $entityId, bool $force = false): ReplicationResult;

	/**
	 * Enable or disable debug logging.
	 */
	public function isDebug(): bool
	{
		return false;
	}

	public function enableTimeZone(): void
	{
		CTimeZone::Enable();
	}

	public function disableTimeZone(): void
	{
		CTimeZone::Disable();
	}

	protected function init(int $entityId): void
	{
		$this->entityId = $entityId;
		$this->producer = $this->getProducer();
		$this->repeater = $this->getRepeater();
		$this->checker = $this->getChecker();
	}

	protected function lazyInit(int $entityId): void
	{
		$this->entityId = $entityId;
	}

	protected function getResult(): ?ReplicationResult
	{
		return $this->replicationResult;
	}

	protected function onBeforeReplicate(bool $do = true): void
	{
		if (!$do)
		{
			return;
		}

		$this->disableTimeZone();
	}

	protected function onAfterReplicate(bool $do = true): void
	{
		if (!$do)
		{
			return;
		}

		$this->enableTimeZone();
	}

	private function logUnexpectedError(Throwable $error): void
	{
		LogFacade::logThrowable($error);
	}
}