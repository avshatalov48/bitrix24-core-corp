<?php

namespace Bitrix\Tasks\Replication\Replicator;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\Replication\CheckerInterface;
use Bitrix\Tasks\Replication\ProducerInterface;
use Bitrix\Tasks\Replication\RepeaterInterface;
use Bitrix\Tasks\Replication\AbstractReplicator;
use Bitrix\Tasks\Replication\ReplicationResult;
use Bitrix\Tasks\Replication\Repository\TemplateRepository;
use Bitrix\Tasks\Replication\RepositoryInterface;
use Bitrix\Tasks\Replication\Template\Repetition\RegularTemplateTaskProducer;
use Bitrix\Tasks\Replication\Template\Repetition\RegularTemplateTaskRepeater;
use Bitrix\Tasks\Replication\Template\Repetition\RegularTemplateTaskReplicationChecker;
use Bitrix\Tasks\Replication\Template\Repetition\Service\TemplateHistoryService;
use Bitrix\Tasks\Replication\Template\Repetition\Time\Service\ExecutionService;
use Bitrix\Tasks\UI;
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

	private const PAYLOAD_KEY = 'agentName';
	private const AGENT_PERIOD = 'N';
	private const AGENT_INTERVAL = 86400;
	private const AGENT_ACTIVE = 'Y';
	private const AGENT_SORT = 150;

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

	public function startReplication(int $templateId): void
	{
		$this->onBeforeReplicate();
		try
		{
			$nextTime = $this->getNextTimeByTemplateId($templateId);

			if ($nextTime !== '')
			{
				$this->addAgent($templateId, $nextTime);
			}
		}
		finally
		{
			$this->onAfterReplicate();
		}
	}

	public function startReplicationAndUpdateTemplate(int $templateId, array $replicateParameters): void
	{
		$this->lazyInit($templateId);
		$this->onBeforeReplicate();
		try
		{
			$nextDateTime = $this->getNextDateTimeByTemplateId($templateId);
			if ($nextDateTime === null)
			{
				return;
			}

			$nextTime = $nextDateTime->toString();
			if ($nextTime === '')
			{
				return;
			}

			// low-level update,because we don't want recursion and events
			$replicateParameters['NEXT_EXECUTION_TIME'] = $nextTime;
			TemplateTable::update($templateId, [
				'REPLICATE_PARAMS' => serialize($replicateParameters),
			]);

			$this->addAgent($templateId, $nextTime);

			$this->writeNextReplicationTimeToTemplateHistory($templateId, $nextDateTime);
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
		return (string)$this->getNextDateTimeByTemplateId($templateId, $baseTime)?->toString();
	}

	public function getNextDateTimeByTemplateId(int $templateId, string $baseTime = ''): ?DateTime
	{
		$repository = (new TemplateRepository($templateId));
		$service = new ExecutionService($repository);
		$result = $service->getTemplateNextExecutionTime($baseTime);
		if (!$result->isSuccess())
		{
			return null;
		}
		$nextExecutionTime = $result->getData()['time'];

		return DateTime::createFromTimestamp($nextExecutionTime)->disableUserTime();
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
		$this->replicationResult = (new ReplicationResult($this))->setData(
				[static::getPayloadKey() => static::EMPTY_AGENT]
			);

		$this->lazyInit($entityId);

		$this->currentResults = [];

		if (!static::isEnabled())
		{
			return $this->replicationResult;
		}

		$this->init($entityId);
		$this->liftLogCleanerAgent();

		if (!$force && $this->checker->stopReplicationByInvalidData())
		{
			return $this->replicationResult;
		}

		if (!$force && $this->checker->stopCurrentReplicationByPostpone())
		{
			$this->replicationResult->setData([static::getPayloadKey() => static::getAgentName($this->entityId)]);

			return $this->replicationResult;
		}

		$this->currentResults[] = $this->producer->produceTask();
		$this->currentResults[] = $this->repeater->repeatTask();

		return $this->replicationResult->merge(...$this->currentResults)->writeToLog();
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
	}

	protected function writeNextReplicationTimeToTemplateHistory(int $templateId, DateTime $nextExecutionDateTime): void
	{
		$template = TemplateObject::wakeUpObject(['ID' => $templateId]);
		$repository = $this->getRepository()->inject($template);

		$historyService = new TemplateHistoryService(
			$repository
		);

		$nextExecutionTimeTS = $nextExecutionDateTime->getTimestamp();
		$timeZoneFromGmtInSeconds = date('Z');
		$period = $nextExecutionTimeTS - time();

		$message = Loc::getMessage('TASKS_REGULAR_TEMPLATE_TASK_REPLICATOR_NEXT_TIME', [
			'#TIME#' => UI::formatDateTime($nextExecutionTimeTS)
				. ' ('
				. UI::formatTimezoneOffsetUTC($timeZoneFromGmtInSeconds)
				. ')',
			'#PERIOD#' => $period,
			'#SECONDS#' => Loc::getMessagePlural('TASKS_REGULAR_TEMPLATE_TASK_REPLICATOR_SECOND', $period),
		]);

		$historyService->write($message);
	}

	protected function addAgent(int $templateId, string $nextTime): void
	{
		CAgent::AddAgent(
			static::getAgentName($templateId),
			'tasks',
			static::AGENT_PERIOD,
			static::AGENT_INTERVAL,
			$nextTime,
			static::AGENT_ACTIVE,
			$nextTime,
			static::AGENT_SORT
		);
	}
}