<?php

namespace Bitrix\Tasks\Replication\Template\Repetition\Time\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Tasks\Replication\AbstractReplicator;
use Bitrix\Tasks\Replication\Template\AbstractParameter;
use Bitrix\Tasks\Replication\Template\Time\Enum\RepeatType;
use Bitrix\Tasks\Replication\Template\Time\Factory\ExecutionTimeFactory;
use Bitrix\Tasks\Replication\Template\Repetition\Parameter\ReplicateParameter;
use Bitrix\Tasks\Replication\Replicator\RegularTemplateTaskReplicator;
use Bitrix\Tasks\Replication\RepositoryInterface;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\User;
use CAgent;

class ExecutionService
{
	public const PRIORITY_TEMPLATE = 0;
	public const PRIORITY_AGENT = 1;

	private AbstractParameter $replicateParameter;
	private Result $currentResult;
	private string $agent;
	private int $nextExecutionTimeTS;
	private ?array $agentData = null;

	public function __construct(private RepositoryInterface $repository)
	{
		$this->init();
	}

	private function init(): void
	{
		$this->agent = $this->getCurrentAgentName();
		$this->replicateParameter = new ReplicateParameter($this->repository);
		$this->currentResult = new Result();
	}

	public function getTemplateCurrentExecutionTime(int $priority = self::PRIORITY_TEMPLATE): string
	{
		$executionTime = (string)$this->replicateParameter->get('NEXT_EXECUTION_TIME');
		$agent = $this->getAgent();
		$agentNextExec = $agent['NEXT_EXEC'] ?? '';

		if ($priority === self::PRIORITY_AGENT && !empty($agentNextExec))
		{
			return $agentNextExec;
		}

		return empty($executionTime) ? $agentNextExec : $executionTime;
	}

	public function getAgent(): array
	{
		if (is_null($this->agentData))
		{
			$agent = CAgent::getList([], ['NAME' => $this->agent])->fetch();
			$this->agentData = is_array($agent) ? $agent : [];
		}

		return $this->agentData;
	}

	public function getTemplateNextExecutionTime(string $lastExecutionTime): Result
	{
		$template = $this->repository->getEntity();

		// deprecated, TIMEZONE_OFFSET parameter is always 0 in new templates
		$timeZoneOffsetTS = $this->replicateParameter->get('TIMEZONE_OFFSET');
		$creatorTimeZoneOffsetTS = $timeZoneOffsetTS ?? User::getTimeZoneOffset($template->getCreatedBy());

		// prepare time to be forced to
		$replicationTimeTS = strtotime($this->replicateParameter->get('TIME'));
		$creatorPreferredTimeTS = UI::parseTimeAmount(
			date("H:i", $replicationTimeTS - $creatorTimeZoneOffsetTS), 'HH:MI'
		);

		// prepare base time
		$baseExecutionTimeTS = time();

		if ($lastExecutionTime) // agent were found and had legal next_time
		{
			$lastExecutionTimeTS = (int)MakeTimeStamp($lastExecutionTime);
			if ($lastExecutionTimeTS > 0)
			{
				$baseExecutionTimeTS = $lastExecutionTimeTS;
			}
		}

		$replicationStartTimeTS = 0;
		$replicationStartTime = $this->replicateParameter->get('START_DATE');
		if ($replicationStartTime)
		{
			$replicationStartTimeTS = MakeTimeStamp($replicationStartTime);
			$replicationStartTimeTS -= $creatorTimeZoneOffsetTS;
		}

		$replicationEndTimeTS = PHP_INT_MAX; // never ending
		$replicationEndTime = $this->replicateParameter->get('END_DATE');
		if ($replicationEndTime)
		{
			$replicationEndTimeTS = MakeTimeStamp($replicationEndTime);
			$replicationEndTimeTS -= $creatorTimeZoneOffsetTS;
		}

		$baseExecutionTimeTS = max($baseExecutionTimeTS, $replicationStartTimeTS);

		$this->nextExecutionTimeTS = ExecutionTimeFactory::getNextExecutionTime(
			$baseExecutionTimeTS,
			$creatorPreferredTimeTS,
			$this->replicateParameter
		);

		if ($this->nextExecutionTimeTS <= 0)
		{
			$this->currentResult->addError(new Error(Loc::getMessage('TASKS_EXECUTION_ILLEGAL_NEXT_TIME')));
			return $this->stopReplication();
		}

		$repeatTill = $this->replicateParameter->get('REPEAT_TILL');

		if (!$repeatTill || $repeatTill === RepeatType::ENDLESS)
		{
			return $this->continueReplication();
		}

		if (
			$repeatTill === RepeatType::DATE
			&& $replicationEndTimeTS > 0
			&& $this->nextExecutionTimeTS > $replicationEndTimeTS
		)
		{
			$this->currentResult->addError(new Error(Loc::getMessage('TASKS_EXECUTION_END_DATE_REACHED')));
			return $this->stopReplication();
		}

		if ($repeatTill === RepeatType::TIMES)
		{
			$neededReplicationCount = (int)$this->replicateParameter->get('TIMES');
			$currentReplicationCount = $template->getTparamReplicationCount();

			if ($currentReplicationCount >= $neededReplicationCount)
			{
				$this->currentResult->addError(new Error(Loc::getMessage('TASKS_EXECUTION_LIMIT_REACHED')));
				return $this->stopReplication();
			}
		}

		return $this->continueReplication();
	}

	public function getCurrentAgentName(): string
	{
		$template = $this->repository->getEntity();
		if (is_null($template))
		{
			return '';
		}

		return str_replace('#ID#', $template->getId(), RegularTemplateTaskReplicator::AGENT_TEMPLATE);
	}

	public function getParameter(): AbstractParameter
	{
		return $this->replicateParameter;
	}

	private function stopReplication(): Result
	{
		$this->currentResult->setData(['time' => 0]);
		return $this->currentResult;
	}

	private function continueReplication(): Result
	{
		$this->currentResult->setData(['time' => $this->nextExecutionTimeTS]);
		return $this->currentResult;
	}
}