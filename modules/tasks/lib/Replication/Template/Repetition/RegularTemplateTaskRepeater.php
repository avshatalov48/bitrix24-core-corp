<?php

namespace Bitrix\Tasks\Replication\Template\Repetition;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Tasks\Control\Template;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Replication\RepeaterInterface;
use Bitrix\Tasks\Replication\Template\Repetition\Parameter\ReplicateParameter;
use Bitrix\Tasks\Replication\RepositoryInterface;
use Bitrix\Tasks\Replication\Template\AbstractParameter;
use Bitrix\Tasks\Replication\Template\Repetition\Time\Service\ExecutionService;
use Bitrix\Tasks\Replication\Replicator\RegularTemplateTaskReplicator;
use Bitrix\Tasks\Replication\Template\Repetition\Service\TemplateHistoryService;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\User;
use Exception;

class RegularTemplateTaskRepeater implements RepeaterInterface
{
	private const MAX_ITERATION_COUNT = 10000;
	private const PERIOD_MINIMUM = 1; // if $pPERIOD less than that value we probably get an error

	private int $recalculateCounter = 0;

	private TemplateHistoryService $templateHistoryService;
	private AbstractParameter $replicateParameter;
	private ExecutionService $executionService;
	private Result $currentResult;

	private int $nextExecutionTimeTS;
	private $additionalData = null;

	public function __construct(private RepositoryInterface $repository)
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

		$this->recalculateNextTimeIfPeriodCorrupted();

		$this->updateTemplate();
		$this->updateAgentPeriod();
		$this->writeToTemplateHistoryNextExecutionTime();

		return $this->continueReplication();
	}

	private function updateTemplate(): Result
	{
		$result = new Result();

		$template = $this->repository->getEntity();
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
				'REPLICATE_PARAMS' => serialize($updatedReplicateParams),
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
		if (Option::get('tasks', RegularTemplateTaskReplicator::DEBUG_KEY, 'N') === 'Y')
		{
			return true;
		}

		return false;
	}

	private function writeToTemplateHistoryWithOldResult(int $nextExecutionTimeTS, string $lastExecutionTime): void
	{
		$result = new Result();
		$templateData = $this->repository->getEntity()->collectValues();
		$templateData['REPLICATE_PARAMS'] = unserialize($templateData['REPLICATE_PARAMS'], ['allowed_classes' => false]);
		$oldNextExecutionTimeTS = (int)MakeTimeStamp(
			Util\Replicator\Task\FromTemplate::getNextTime($templateData, $lastExecutionTime)->getData()['TIME']
		);
		$oldNextExecutionTime = UI::formatDateTime($oldNextExecutionTimeTS);
		$nextExecutionTime = UI::formatDateTime($nextExecutionTimeTS);

		$message = "Replicator/V1: {$oldNextExecutionTimeTS} (s.) / {$oldNextExecutionTime}, Replicator/V2: {$nextExecutionTimeTS} (s.) / {$nextExecutionTime}";

		if ($oldNextExecutionTimeTS !== $nextExecutionTimeTS)
		{
			$templateId = $this->repository->getEntity()->getId();
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

		$message = Loc::getMessage('TASKS_REGULAR_TEMPLATE_TASK_REPEATER_NEXT_TIME', [
			'#TIME#' => UI::formatDateTime($this->nextExecutionTimeTS)
				. ' ('
				. UI::formatTimezoneOffsetUTC($timeZoneFromGmtInSeconds)
				. ')',
			'#PERIOD#' => $pPERIOD,
			'#SECONDS#' => Loc::getMessagePlural('TASKS_REGULAR_TEMPLATE_TASK_REPEATER_SECOND', $pPERIOD),
		]);

		if ($message)
		{
			$this->templateHistoryService->write(
				$message,
				$this->currentResult,
			);
		}
	}

	private function writeToLogInterruptedPeriod(int $period, string $marker): void
	{
		$data = [
			'message' => 'Period may be less than zero, ' . $marker,
			'period' => $period,
			'data' => $this->repository->getEntity()->toArray(),
			'nextTime' => $this->executionService->getTemplateCurrentExecutionTime(),
		];

		(new Log($marker))->collect($data);
	}

	private function writeToLogLoopError(string $nextExecutionTime, string $lastExecutionTime, int $iterationCount): void
	{
		$template = $this->repository->getEntity();
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
				User::getTimeZoneOffset($createdBy),
				$this->replicateParameter->get('TIME'),
				// deprecated, TIMEZONE_OFFSET parameter is always 0 in new templates
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

	private function calculateNextReplicationTimeTS(int $currentExecutionTimePriority = ExecutionService::PRIORITY_TEMPLATE): Result
	{
		$result = new Result();

		$iterationCount = 0;
		$currentExecutionTime = $this->executionService->getTemplateCurrentExecutionTime($currentExecutionTimePriority);
		$lastExecutionTime = $currentExecutionTime;
		do
		{
			$nextExecutionTimeResult = $this->executionService->getTemplateNextExecutionTime($lastExecutionTime);
			$nextExecutionTimeData = $nextExecutionTimeResult->getData();
			$nextExecutionTimeTS = $nextExecutionTimeData['time'];
			$this->writeToTemplateHistoryWithOldResult($nextExecutionTimeTS, $lastExecutionTime);

			if ($nextExecutionTimeTS <= 0)
			{
				$message = Loc::getMessage('TASKS_REGULAR_TEMPLATE_TASK_REPEATER_PROCESS_STOPPED');
				if ($message)
				{
					$this->templateHistoryService->write($message, $result);
				}
				$result->addError(new Error(Loc::getMessage('TASKS_REGULAR_TEMPLATE_TASK_REPEATER_PROCESS_STOPPED')));

				return $result;
			}

			$lastExecutionTimeTS = MakeTimeStamp($lastExecutionTime);
			$nextExecutionTime = UI::formatDateTime($nextExecutionTimeTS);

			if (($nextExecutionTime && $lastExecutionTimeTS >= $nextExecutionTimeTS) || ($iterationCount > static::MAX_ITERATION_COUNT))
			{
				$message = Loc::getMessage('TASKS_REGULAR_TEMPLATE_TASK_REPEATER_PROCESS_ERROR');
				if ($message)
				{
					$this->templateHistoryService->write($message, $result);
				}
				$this->writeToLogLoopError($nextExecutionTime, $lastExecutionTime, $iterationCount);
				$result->addError(new Error(Loc::getMessage('TASKS_REGULAR_TEMPLATE_TASK_REPEATER_PROCESS_ERROR')));

				return $result;
			}

			$lastExecutionTime = $nextExecutionTime;
			$currentServerTimeTS = time();

			$iterationCount++;
		}
		while (
			($nextExecutionTimeResult->isSuccess() && $nextExecutionTime)
			&& $nextExecutionTimeTS < $currentServerTimeTS
		);

		$result->setData(['time' => $nextExecutionTimeTS]);
		return $result;
	}

	private function stopReplication(): Result
	{
		$this->currentResult->setData([RegularTemplateTaskReplicator::getPayloadKey() => RegularTemplateTaskReplicator::EMPTY_AGENT]);
		return $this->currentResult;
	}

	private function continueReplication(): Result
	{
		$this->currentResult->setData([
			RegularTemplateTaskReplicator::getPayloadKey() => RegularTemplateTaskReplicator::getAgentName($this->repository->getEntity()
				->getId()),
		]);

		return $this->currentResult;
	}

	public function getAdditionalData()
	{
		return $this->additionalData;
	}

	public function setAdditionalData($data): void
	{
		$this->additionalData = $data;
	}

	private function recalculateNextTimeIfPeriodCorrupted(): void
	{
		if (!RegularTemplateTaskReplicator::isRecalculationEnabled() || !$this->isPeriodCorrupted())
		{
			return;
		}

		++$this->recalculateCounter;

		$this->writeToLogInterruptedPeriod(
			$this->getPeriod(),
			'TASKS_REPLICATOR_CORRUPTED_PERIOD_BEFORE_RECALCULATE_' . $this->recalculateCounter
		);

		$this->currentResult = $this->calculateNextReplicationTimeTS(ExecutionService::PRIORITY_AGENT);
		$this->nextExecutionTimeTS = $this->currentResult->getData()['time'];

		$marker = $this->isPeriodCorrupted()
			? 'TASKS_REPLICATOR_CORRUPTED_PERIOD_AFTER_RECALCULATE_' . $this->recalculateCounter
			: 'TASKS_REPLICATOR_RESTORED_PERIOD_AFTER_RECALCULATE_' . $this->recalculateCounter;

		$this->writeToLogInterruptedPeriod(
			$this->getPeriod(),
			$marker
		);
	}

	private function isPeriodCorrupted(): bool
	{
		return $this->getPeriod() <= static::PERIOD_MINIMUM;
	}

	private function getPeriod(): int
	{
		return $this->nextExecutionTimeTS - time();
	}
}