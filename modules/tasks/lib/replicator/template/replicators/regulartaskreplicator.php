<?php

namespace Bitrix\Tasks\Replicator\Template\Replicators;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Tasks\Internals\Notification\Controller;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Replicator\AbstractReplicator;
use Bitrix\Tasks\Replicator\CheckerInterface;
use Bitrix\Tasks\Replicator\ProducerInterface;
use Bitrix\Tasks\Replicator\RepeaterInterface;
use Bitrix\Tasks\Replicator\ReplicationResult;
use Bitrix\Tasks\Replicator\Template\Regularity\RegularTaskChecker;
use Bitrix\Tasks\Replicator\Template\Regularity\RegularTaskProducer;
use Bitrix\Tasks\Replicator\Template\Regularity\RegularTaskRepeater;
use Bitrix\Tasks\Replicator\Template\Repository\TaskRepository;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;
use Exception;

class RegularTaskReplicator extends AbstractReplicator
{
	public const DEBUG_KEY = 'tasks_use_regularity_use_debug';
	public const ENABLED_KEY = 'tasks_use_regularity';
	private const PAYLOAD_KEY = 'copiedTaskId';

	private int $copiedTaskId = 0;

	public static function isEnabled(): bool
	{
		return Option::get('tasks', static::ENABLED_KEY, 'N', '-') === 'Y';
	}

	protected function getProducer(): ProducerInterface
	{
		return new RegularTaskProducer($this->getRepository());
	}

	protected function getRepeater(): RepeaterInterface
	{
		return new RegularTaskRepeater($this->getRepository());
	}

	protected function getChecker(): CheckerInterface
	{
		return new RegularTaskChecker($this->getRepository());
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

		if (!$force && $this->checker->stopReplicationByInvalidData())
		{
			return $this->replicationResult;
		}

		if (!$force && $this->checker->stopCurrentReplicationByPostpone())
		{
			return $this->replicationResult;
		}

		$this->replicationResult = $this->replicationResult->convertFromResult($this->producer->produceTask());
		if (!$this->replicationResult->isSuccess())
		{
			return $this->replicationResult;
		}

		$this->copiedTaskId = $this->replicationResult->getPayload();
		$this->repeater->setAdditionalData([static::getPayloadKey() => $this->copiedTaskId]);

		$this->currentResults[] = $this->repeater->repeatTask();
		$this->currentResults[] = $this->sendRegularTaskReplicatedNotifications();

		return $this->replicationResult
			->merge(...$this->currentResults)
			->writeToLog();
	}

	public function isDebug(): bool
	{
		return Option::get('tasks', static::DEBUG_KEY, 'Y', '-') === 'Y';
	}

	protected function sendRegularTaskReplicatedNotifications(): Result
	{
		$result = new Result();

		$task = TaskRegistry::getInstance()->getObject($this->copiedTaskId, true);
		try
		{
			(new Controller())
				->onRegularTaskReplicated($task)
				->push();
		}
		catch (Exception $exception)
		{
			$result->addError(Error::createFromThrowable($exception));
		}
		finally
		{
			return $result;
		}
	}

	public static function getPayloadKey(): string
	{
		return static::PAYLOAD_KEY;
	}
}