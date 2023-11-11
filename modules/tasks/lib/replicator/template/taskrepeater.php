<?php

namespace Bitrix\Tasks\Replicator\Template;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Tasks\Control\Template;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Replicator\Repeater;
use Bitrix\Tasks\Replicator\Replicator;
use Bitrix\Tasks\Replicator\Template\Repetition\Time\Service\ExecutionService;
use Bitrix\Tasks\Replicator\Template\Service\TemplateHistoryService;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\User;
use Exception;

class TaskRepeater implements Repeater
{
	private const MAX_ITERATION_COUNT = 10000;

	private TemplateHistoryService $templateHistoryService;
	private Parameter $replicateParameter;
	private ExecutionService $executionService;
	private Result $currentResult;
	private bool $debug = false;
	private int $nextExecutionTimeTS;

	public function __construct(private Repository $repository)
	{
		$this->init();
	}

	private function init(): void
	{
		$this->templateHistoryService = new TemplateHistoryService($this->repository);
		$this->replicateParameter = new ReplicateParameter($this->repository);
		$this->executionService = new ExecutionService($this->repository);
	}

	public function repeatTask(): Result
	{
		$this->currentResult = $this->calculateNextReplicationTimeTS();
		if (!$this->currentResult->isSuccess())
		{
			return $this->stopReplication();
		}

		$this->nextExecutionTimeTS = $this->currentResult->getData()['time'];
		$this->updateTemplate();
		$this->updateAgentPeriod();
		$this->writeToTemplateHistoryNextExecutionTime();

		return $this->continueReplication();
	}

	private function updateTemplate(): Result
	{
		$result = new Result();

		$template = $this->repository->getTemplate();
		$newNextExecutionTime = UI::formatDateTime($this->nextExecutionTimeTS);

		$updatedReplicateParams = $this->replicateParameter->getData();
		$updatedReplicateParams['NEXT_EXECUTION_TIME'] = $newNextExecutionTime;

		$userId = User::getId();
		if(!$userId)
		{
			$userId = User::getAdminId(); // compatibility
		}

		$handler = new Template($userId);
		$handler->withSkipAgent();
		try
		{
			$handler->update($template->getId(), [
				'REPLICATE_PARAMS' => serialize($updatedReplicateParams)
			]);
		}
		catch (Exception $exception)
		{
			$result->addError(new Error($exception->getMessage()));
			return $result;
		}

		return $result;
	}

	public function isDebug(): bool
	{
		if (Option::get('tasks', Replicator::DEBUG_KEY, 'N') === 'Y')
		{
			return true;
		}

		return false;
	}

	private function writeToTemplateHistoryWithOldResult(int $nextExecutionTimeTS, string $lastExecutionTime): void
	{
		$result = new Result();
		$templateData = $this->repository->getTemplate()->collectValues();
		$templateData['REPLICATE_PARAMS'] = unserialize($templateData['REPLICATE_PARAMS'], ['allowed_classes' => false]);
		$oldNextExecutionTimeTS = (int)MakeTimeStamp(
			Util\Replicator\Task\FromTemplate::getNextTime($templateData, $lastExecutionTime)->getData()['TIME']
		);
		$oldNextExecutionTime = UI::formatDateTime($oldNextExecutionTimeTS);
		$nextExecutionTime = UI::formatDateTime($nextExecutionTimeTS);

		$message = "Replicator/V1: {$oldNextExecutionTimeTS} (s.) / {$oldNextExecutionTime}, Replicator/V2: {$nextExecutionTimeTS} (s.) / {$nextExecutionTime}";

		if ($oldNextExecutionTimeTS !== $nextExecutionTimeTS)
		{
			$templateId = $this->repository->getTemplate()->getId();
			$errorMessage = "Time received from replicators varies. (#{$templateId})";
			$result->addError(new Error($errorMessage));
			(new Log())->collect("{$message}: {$errorMessage}");

		}
		if ($this->isDebug())
		{
			$this->templateHistoryService->write($message, $result);
		}
	}

	private function writeToTemplateHistoryNextExecutionTime(): void
	{
		global $pPERIOD;

		$timeZoneFromGmtInSeconds = date('Z');

		$message = Loc::getMessage('TASKS_REPEATER_NEXT_TIME', [
			'#TIME#' => UI::formatDateTime($this->nextExecutionTimeTS)
				. ' ('
				. UI::formatTimezoneOffsetUTC($timeZoneFromGmtInSeconds)
				. ')',
			'#PERIOD#' => $pPERIOD,
			'#SECONDS#' => Loc::getMessagePlural('TASKS_REPEATER_SECOND', $pPERIOD),
		]);

		if ($message)
		{
			$this->templateHistoryService->write(
				$message,
				$this->currentResult,
			);
		}
	}

	private function writeToLogLoopError(string $nextExecutionTime, string $lastExecutionTime, int $iterationCount): void
	{
		$template = $this->repository->getTemplate();
		if ($iterationCount > static::MAX_ITERATION_COUNT)
		{
			$message = 'insane iteration count reached while calculating next execution time';
		}
		else
		{
			$createdBy = $template['CREATED_BY'];

			$eDebug = [
				$createdBy,
				time(),
				User::getTimeZoneOffsetCurrentUser(),
				User::getTimeZoneOffset($createdBy),
				$this->replicateParameter->get('TIME'),
				$this->replicateParameter->get('TIMEZONE_OFFSET'),
				$iterationCount,
			];
			$message = 'getTemplateNextExecutionTime() loop detected for replication by template '
				. $template->getId()
				. ' ('
				. $nextExecutionTime
				. ' => '
				. $lastExecutionTime
				. ') ('
				. implode(', ', $eDebug)
				. ')';
		}

		(new Log())->collect($message);
	}

	private function updateAgentPeriod(): void
	{
		// we can not use CAgent::Update() here, kz the agent will be updated again just after this function ends ...
		global $pPERIOD;
		// ... but we may set some global var called $pPERIOD
		// "why ' - time()'?" you may ask. see CAgent::ExecuteAgents(), in the last sql we got:
		// NEXT_EXEC=DATE_ADD(".($arAgent["IS_PERIOD"]=="Y"? "NEXT_EXEC" : "now()").", INTERVAL ".$pPERIOD." SECOND),
		$pPERIOD = $this->nextExecutionTimeTS - time();
	}

	private function calculateNextReplicationTimeTS(): Result
	{
		$result = new Result();

		$iterationCount = 0;
		$currentExecutionTime = $this->executionService->getTemplateCurrentExecutionTime();
		$currentUserTimezoneTS = User::getTimeZoneOffsetCurrentUser();
		$lastExecutionTime = $currentExecutionTime;
		do
		{
			$nextExecutionTimeResult = $this->executionService->getTemplateNextExecutionTime($lastExecutionTime);
			$nextExecutionTimeData = $nextExecutionTimeResult->getData();
			$nextExecutionTimeTS = $nextExecutionTimeData['time'];
			$this->writeToTemplateHistoryWithOldResult($nextExecutionTimeTS, $lastExecutionTime);

			if ($nextExecutionTimeTS <= 0)
			{
				$message = Loc::getMessage('TASKS_REPEATER_PROCESS_STOPPED');
				if ($message)
				{
					$this->templateHistoryService->write($message, $result);
				}
				$result->addError(new Error(Loc::getMessage('TASKS_REPEATER_PROCESS_STOPPED')));

				return $result;
			}

			$lastExecutionTimeTS = MakeTimeStamp($lastExecutionTime);
			$nextExecutionTime = UI::formatDateTime($nextExecutionTimeTS);

			if (($nextExecutionTime && $lastExecutionTimeTS >= $nextExecutionTimeTS) || ($iterationCount > static::MAX_ITERATION_COUNT))
			{
				$message = Loc::getMessage('TASKS_REPEATER_PROCESS_ERROR');
				if ($message)
				{
					$this->templateHistoryService->write($message, $result);
				}
				$this->writeToLogLoopError($nextExecutionTime, $lastExecutionTime, $iterationCount);
				$result->addError(new Error(Loc::getMessage('TASKS_REPEATER_PROCESS_ERROR')));

				return $result;
			}

			$lastExecutionTime = $nextExecutionTime;
			$currentUserTimeTS = time() + $currentUserTimezoneTS;

			$iterationCount++;
		}
		while (
			($nextExecutionTimeResult->isSuccess() && $nextExecutionTime)
			&& $nextExecutionTimeTS < $currentUserTimeTS
		);

		$result->setData(['time' => $nextExecutionTimeTS - $currentUserTimezoneTS]);
		return $result;
	}

	private function stopReplication(): Result
	{
		$this->currentResult->setData([Replicator::AGENT_NAME_PARAMETER => Replicator::EMPTY_AGENT]);
		return $this->currentResult;
	}

	private function continueReplication(): Result
	{
		$this->currentResult->setData([
			Replicator::AGENT_NAME_PARAMETER => Replicator::getAgentName($this->repository->getTemplate()->getId())
		]);

		return $this->currentResult;
	}
}
