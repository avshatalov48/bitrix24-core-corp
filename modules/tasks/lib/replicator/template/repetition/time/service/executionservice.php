<?php

namespace Bitrix\Tasks\Replicator\Template\Repetition\Time\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Tasks\Replicator\AbstractReplicator;
use Bitrix\Tasks\Replicator\Template\AbstractParameter;
use Bitrix\Tasks\Replicator\Template\Time\Enum\RepeatType;
use Bitrix\Tasks\Replicator\Template\Time\Factory\ExecutionTimeFactory;
use Bitrix\Tasks\Replicator\Template\Repetition\Parameter\ReplicateParameter;
use Bitrix\Tasks\Replicator\Template\Replicators\RegularTemplateTaskReplicator;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\User;
use CAgent;

class ExecutionService
{
	private AbstractParameter $replicateParameter;
	private Result $currentResult;
	private string $agent;
	private int $nextExecutionTimeTS;

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

	public function getTemplateCurrentExecutionTime(): string
	{
		$executionTime = (string)$this->replicateParameter->get('NEXT_EXECUTION_TIME');
		if (!empty($executionTime))
		{
			return $executionTime;
		}

		$agent = CAgent::getList([], ['NAME' => $this->agent])->fetch();
		if ($agent)
		{
			return $agent['NEXT_EXEC'];
		}

		return '';
	}

	public function getTemplateNextExecutionTime(string $lastExecutionTime): Result
	{
		$template = $this->repository->getEntity();

		// get users and their time zone offsets
		$currentUserTimeZoneOffsetTS = User::getTimeZoneOffsetCurrentUser();

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
				// $agentTime is in current user`s time, but we want server time here
				$lastExecutionTimeTS -= $currentUserTimeZoneOffsetTS;
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
			$this->nextExecutionTimeTS += $currentUserTimeZoneOffsetTS;
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

		$this->nextExecutionTimeTS += $currentUserTimeZoneOffsetTS;

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