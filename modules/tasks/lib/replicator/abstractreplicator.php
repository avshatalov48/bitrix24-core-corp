<?php

namespace Bitrix\Tasks\Replicator;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;
use CTimeZone;

abstract class AbstractReplicator
{
	protected int $entityId;
	protected ReplicationResult $replicationResult;
	/** @var Result[] $currentResults */
	protected array $currentResults = [];
	protected ProducerInterface $producer;
	protected RepeaterInterface $repeater;
	protected CheckerInterface $checker;

	public function __construct(protected int $userId = 0)
	{
	}

	abstract public static function isEnabled(): bool;

	abstract public static function getPayloadKey(): string;

	abstract protected function getProducer(): ProducerInterface;

	abstract protected function getRepeater(): RepeaterInterface;

	abstract protected function getChecker(): CheckerInterface;

	abstract protected function getRepository(): RepositoryInterface;

	public function replicate(int $entityId, bool $force = false): ReplicationResult
	{
		try
		{
			$this->onBeforeReplicate($force);

			return $this->replicateImplementation($entityId, $force);
		}
		finally
		{
			$this->onAfterReplicate($force);
		}
	}

	abstract protected function replicateImplementation(int $entityId, bool $force = false): ReplicationResult;

	public function isDebug(): bool
	{
		return false;
	}

	protected function init(int $entityId): void
	{
		$this->entityId = $entityId;
		$this->producer = $this->getProducer();
		$this->repeater = $this->getRepeater();
		$this->checker = $this->getChecker();
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

		CTimeZone::Disable();
	}

	protected function onAfterReplicate(bool $do = true): void
	{
		if (!$do)
		{
			return;
		}

		CTimeZone::Enable();
	}

	public function addDisabledError(): void
	{
		$this->replicationResult->addError(new Error(static::class . ' is disabled'));
	}
}