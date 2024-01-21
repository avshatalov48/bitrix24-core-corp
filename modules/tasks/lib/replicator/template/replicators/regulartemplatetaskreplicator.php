<?php

namespace Bitrix\Tasks\Replicator\Template\Replicators;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Result;
use Bitrix\Tasks\Replicator\CheckerInterface;
use Bitrix\Tasks\Replicator\ProducerInterface;
use Bitrix\Tasks\Replicator\RepeaterInterface;
use Bitrix\Tasks\Replicator\AbstractReplicator;
use Bitrix\Tasks\Replicator\ReplicationResult;
use Bitrix\Tasks\Replicator\Template\Repository\TemplateRepository;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;
use Bitrix\Tasks\Replicator\Template\Repetition\RegularTemplateTaskProducer;
use Bitrix\Tasks\Replicator\Template\Repetition\RegularTemplateTaskRepeater;
use Bitrix\Tasks\Replicator\Template\Repetition\RegularTemplateTaskReplicationChecker;
use Bitrix\Tasks\Util\AgentManager;

class RegularTemplateTaskReplicator extends AbstractReplicator
{
	public const DEBUG_KEY = 'tasks_use_new_replication_use_debug';
	public const AGENT_TEMPLATE = 'CTasks::RepeatTaskByTemplateId(#ID#);';
	public const EMPTY_AGENT = '';
	public const ENABLED_KEY = 'tasks_use_new_replication';
	private const PAYLOAD_KEY = 'agentName';

	public static function getAgentName(int $templateId): string
	{
		return str_replace('#ID#', $templateId, static::AGENT_TEMPLATE);
	}

	public static function isEnabled(): bool
	{
		return Option::get('tasks', static::ENABLED_KEY, 'N', '-') === 'Y';
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
		return new TemplateRepository($this->entityId);
	}

	protected function replicateImplementation(int $entityId, bool $force = false): ReplicationResult
	{
		$this->currentResults = [];
		$this->replicationResult = (new ReplicationResult($this))
				->setData([$this->getPayloadKey() => static::EMPTY_AGENT]);

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
			$this->replicationResult->setData([$this->getPayloadKey() => static::getAgentName($this->entityId)]);
			return $this->replicationResult;
		}

		$this->currentResults[]= $this->producer->produceTask();
		$this->currentResults[]= $this->repeater->repeatTask();

		return $this->replicationResult
			->merge(...$this->currentResults)
			->writeToLog();
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
		return Option::get('tasks', static::DEBUG_KEY, 'Y', '-') === 'Y';
	}


	public static function getPayloadKey(): string
	{
		return static::PAYLOAD_KEY;
	}
}