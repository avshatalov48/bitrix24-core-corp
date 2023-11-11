<?php

namespace Bitrix\Tasks\Replicator;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Replicator\Template\Repository;
use Bitrix\Tasks\Util\AgentManager;

abstract class Replicator
{
	public const AGENT_TEMPLATE = 'CTasks::RepeatTaskByTemplateId(#ID#);';
	public const EMPTY_AGENT = '';
	public const AGENT_NAME_PARAMETER = 'agentName';
	public const NEW_REPLICATION_KEY = 'tasks_use_new_replication';
	public const DEBUG_KEY = 'tasks_use_new_replication_use_debug';

	protected int $templateId;
	protected Result $currentResult;
	protected Producer $producer;
	protected Repeater $repeater;
	protected Checker $checker;

	public static function getAgentName(int $templateId): string
	{
		return str_replace('#ID#', $templateId, static::AGENT_TEMPLATE);
	}

	public function __construct(protected int $userId = 0)
	{
		Loc::loadMessages(__FILE__);
	}

	abstract public static function isEnabled(): bool;
	abstract protected function getProducer(): Producer;
	abstract protected function getRepeater(): Repeater;
	abstract protected function getChecker(): Checker;
	abstract protected function getRepository(): Repository;

	public function replicate(int $templateId, bool $force = false): string
	{
		$this->init($templateId);
		if (!$force && $this->checker->stopReplicationByInvalidData())
		{
			return static::EMPTY_AGENT;
		}

		if (!$force && $this->checker->stopCurrentReplicationByPostpone())
		{
			return static::getAgentName($this->templateId);
		}

		$this->currentResult = $this->producer->produceTask();
		$this->writeToLog();

		$this->currentResult = $this->repeater->repeatTask();
		$this->writeToLog();

		return $this->currentResult->getData()[static::AGENT_NAME_PARAMETER];
	}

	private function init(int $templateId): void
	{
		$this->templateId = $templateId;
		$this->producer = $this->getProducer();
		$this->repeater = $this->getRepeater();
		$this->checker = $this->getChecker();
		$this->liftLogCleanerAgent();
	}

	private function isDebug(): bool
	{
		return Option::get('tasks', static::DEBUG_KEY, 'N') === 'Y';
	}

	private function writeToLog(): void
	{
		if (!$this->isDebug())
		{
			return;
		}
		if ($this->currentResult->isSuccess())
		{
			return;
		}

		$errors = $this->currentResult->getErrorCollection()->toArray();
		(new Log())->collect('Replicator/V2 Debug: ' . var_export($errors, true));
	}

	private function liftLogCleanerAgent(): void
	{
		AgentManager::checkAgentIsAlive(
			AgentManager::LOG_CLEANER_AGENT_NAME,
			AgentManager::LOG_CLEANER_AGENT_INTERVAL
		);
	}
}