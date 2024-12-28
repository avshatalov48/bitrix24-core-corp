<?php

namespace Bitrix\Tasks\Replication\Replicator;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\Replication\AbstractMutex;
use Bitrix\Tasks\Replication\CheckerInterface;
use Bitrix\Tasks\Replication\ProducerInterface;
use Bitrix\Tasks\Replication\RepeaterInterface;
use Bitrix\Tasks\Replication\AbstractReplicator;
use Bitrix\Tasks\Replication\ReplicationResult;
use Bitrix\Tasks\Replication\Repository\TemplateRepository;
use Bitrix\Tasks\Replication\RepositoryInterface;
use Bitrix\Tasks\Replication\Template\Common\Mutex\EntityMutex;
use Bitrix\Tasks\Replication\Template\Repetition\RegularTemplateTaskProducer;
use Bitrix\Tasks\Replication\Template\Repetition\RegularTemplateTaskRepeater;
use Bitrix\Tasks\Replication\Template\Repetition\RegularTemplateTaskReplicationChecker;
use Bitrix\Tasks\Replication\Template\Repetition\Time\Service\ExecutionService;
use Bitrix\Tasks\Util\AgentManager;
use CAgent;
use Throwable;

class RegularTemplateTaskReplicator extends AbstractReplicator
{
	public const DEBUG_KEY = 'tasks_use_new_replication_use_debug';
	public const AGENT_TEMPLATE = 'CTasks::RepeatTaskByTemplateId(#ID#);';
	public const EMPTY_AGENT = '';
	public const ENABLED_KEY = 'tasks_use_new_replication';
	public const ENABLE_RECALCULATION = 'tasks_use_recalculation';
	public const ENABLE_TIME_PRIORITY = 'tasks_use_time_priority';
	public const ENABLE_MUTEX = 'tasks_use_mutex';

	private const PAYLOAD_KEY = 'agentName';
	private const AGENT_PERIOD = 'N';
	private const AGENT_INTERVAL = 86400;
	private const AGENT_ACTIVE = 'Y';

	protected AbstractMutex $mutex;

	public static function getAgentName(int $templateId): string
	{
		return str_replace('#ID#', $templateId, static::AGENT_TEMPLATE);
	}

	public static function isEnabled(): bool
	{
		return Option::get('tasks', static::ENABLED_KEY, 'Y') === 'Y';
	}

	public static function isRecalculationEnabled(): bool
	{
		return Option::get('tasks', static::ENABLE_RECALCULATION, 'Y') === 'Y';
	}

	public static function isTimePriorityEnabled(): bool
	{
		return Option::get('tasks', static::ENABLE_TIME_PRIORITY, 'N') === 'Y';
	}

	public static function isMutexEnabled(): bool
	{
		return Option::get('tasks', static::ENABLE_MUTEX, 'N') === 'Y';
	}

	public function startReplication(int $templateId): void
	{
		$this->onBeforeReplicate();
		try
		{
			$nextTime = $this->getNextTimeByTemplateId($templateId);

			if ($nextTime !== '')
			{
				CAgent::AddAgent(
					static::getAgentName($templateId),
					'tasks',
					static::AGENT_PERIOD,
					static::AGENT_INTERVAL,
					$nextTime,
					static::AGENT_ACTIVE,
					$nextTime
				);
			}
		}
		finally
		{
			$this->onAfterReplicate();
		}
	}

	public function startReplicationAndUpdateTemplate(int $templateId, array $replicateParameters): void
	{
		$this->onBeforeReplicate();
		try
		{
			$nextTime = $this->getNextTimeByTemplateId($templateId);
			if ($nextTime === '')
			{
				return;
			}

			// low-level update,because we don't want recursion and events
			$replicateParameters['NEXT_EXECUTION_TIME'] = $nextTime;
			TemplateTable::update($templateId, [
				'REPLICATE_PARAMS' => serialize($replicateParameters),
			]);

			CAgent::AddAgent(
				static::getAgentName($templateId),
				'tasks',
				static::AGENT_PERIOD,
				static::AGENT_INTERVAL,
				$nextTime,
				static::AGENT_ACTIVE,
				$nextTime
			);
		}
		catch (Throwable $t)
		{
			LogFacade::logThrowable($t);
		}
		finally
		{
			$this->onAfterReplicate();
		}
	}

	public function stopReplication(int $templateId): void
	{
		CAgent::RemoveAgent(static::getAgentName($templateId), 'tasks');

		// compatability
		CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('.$templateId.', 0);', 'tasks');
		CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('.$templateId.', 1);', 'tasks');
	}

	public function getNextTimeByTemplateId(int $templateId, string $baseTime = ''): string
	{
		$repository = (new TemplateRepository($templateId));
		$service = new ExecutionService($repository);
		$result = $service->getTemplateNextExecutionTime($baseTime);
		if (!$result->isSuccess())
		{
			return '';
		}
		$nextExecutionTime = $result->getData()['time'];

		return DateTime::createFromTimestamp($nextExecutionTime)->disableUserTime()->toString();
	}

	protected function getProducer(): ProducerInterface
	{
		return new RegularTemplateTaskProducer($this->getRepository());
	}

	protected function getRepeater(): RepeaterInterface
	{
		return new RegularTemplateTaskRepeater($this->getRepository());
	}

	protected function getChecker(): CheckerInterface
	{
		return new RegularTemplateTaskReplicationChecker($this->getRepository());
	}

	protected function getRepository(): RepositoryInterface
	{
		return TemplateRepository::getInstance($this->entityId);
	}

	protected function replicateImplementation(int $entityId, bool $force = false): ReplicationResult
	{
		$this->replicationResult = (new ReplicationResult($this))
			->setData([static::getPayloadKey() => static::EMPTY_AGENT]);

		$this->lazyInit($entityId);

		if ($this->mutex->lock())
		{
			$this->currentResults = [];

			if (!static::isEnabled())
			{
				$this->mutex->unlock();
				return $this->replicationResult;
			}

			$this->init($entityId);
			$this->liftLogCleanerAgent();

			if (!$force && $this->checker->stopReplicationByInvalidData())
			{
				$this->mutex->unlock();
				return $this->replicationResult;
			}

			if (!$force && $this->checker->stopCurrentReplicationByPostpone())
			{
				$this->replicationResult->setData([static::getPayloadKey() => static::getAgentName($this->entityId)]);
				$this->mutex->unlock();

				return $this->replicationResult;
			}

			$this->currentResults[]= $this->producer->produceTask();
			$this->currentResults[]= $this->repeater->repeatTask();
			$this->mutex->unlock();

			return $this->replicationResult
				->merge(...$this->currentResults)
				->writeToLog();
		}

		$this->replicationResult->setData([static::getPayloadKey() => static::getAgentName($entityId)]);
		return $this->replicationResult;
	}

	protected function liftLogCleanerAgent(): void
	{
		AgentManager::checkAgentIsAlive(
			AgentManager::LOG_CLEANER_AGENT_NAME,
			AgentManager::LOG_CLEANER_AGENT_INTERVAL
		);
	}

	public function isDebug(): bool
	{
		return Option::get('tasks', static::DEBUG_KEY, 'Y') === 'Y';
	}


	public static function getPayloadKey(): string
	{
		return static::PAYLOAD_KEY;
	}

	protected function lazyInit(int $entityId): void
	{
		parent::lazyInit($entityId);
		$this->mutex ??= new EntityMutex($entityId, static::isMutexEnabled());
	}
}