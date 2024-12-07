<?php

namespace Bitrix\Tasks\Control\Handler;

use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\Status;

class TariffFieldHandler
{
	public function __construct(private array $fields = [])
	{
		$this->prepareFieldsByRestrictions();
	}

	public function getFields(): array
	{
		return $this->fields;
	}

	private function prepareFieldsByRestrictions(): void
	{
		$this->prepareStatus();
		$this->prepareControl();
		$this->prepareMatchWorkTime();
		$this->prepareObserversParticipants();
		$this->prepareTimeTracking();
		$this->prepareStatusSummary();
		$this->prepareReplication();
	}

	private function prepareStatus(): void
	{
		if (!isset($this->fields['STATUS']))
		{
			return;
		}

		if ($this->fields['STATUS'] === Status::SUPPOSEDLY_COMPLETED)
		{
			$taskControlEnabled = Bitrix24::checkFeatureEnabled(
				Bitrix24\FeatureDictionary::TASK_CONTROL
			);
			if (!$taskControlEnabled)
			{
				$this->fields[] = Status::COMPLETED;
			}
		}
	}

	private function prepareControl(): void
	{
		if (!isset($this->fields['TASK_CONTROL']))
		{
			return;
		}

		$taskControlEnabled = Bitrix24::checkFeatureEnabled(
			Bitrix24\FeatureDictionary::TASK_CONTROL
		);
		if (!$taskControlEnabled)
		{
			$this->fields['TASK_CONTROL'] = false;
		}
	}

	private function prepareMatchWorkTime(): void
	{
		if (!isset($this->fields['MATCH_WORK_TIME']))
		{
			return;
		}

		$taskSkipWeekendsEnabled = Bitrix24::checkFeatureEnabled(
			Bitrix24\FeatureDictionary::TASK_SKIP_WEEKENDS
		);
		if (!$taskSkipWeekendsEnabled)
		{
			$this->fields['MATCH_WORK_TIME'] = false;
		}
	}

	private function prepareObserversParticipants(): void
	{
		$taskObserversParticipantsEnabled = Bitrix24::checkFeatureEnabled(
			Bitrix24\FeatureDictionary::TASK_OBSERVERS_PARTICIPANTS
		);

		if (isset($this->fields['ACCOMPLICES']))
		{
			if (!$taskObserversParticipantsEnabled)
			{
				$this->fields['ACCOMPLICES'] = [];
			}
		}

		if (isset($this->fields['AUDITORS']))
		{
			if (!$taskObserversParticipantsEnabled)
			{
				$this->fields['AUDITORS'] = [];
			}
		}
	}

	private function prepareTimeTracking(): void
	{
		if (
			isset($this->fields['ALLOW_TIME_TRACKING'])
			&& $this->fields['ALLOW_TIME_TRACKING'] === 'Y'
		)
		{
			$taskTimeTrackingEnabled = Bitrix24::checkFeatureEnabled(
				Bitrix24\FeatureDictionary::TASK_TIME_TRACKING
			);
			if (!$taskTimeTrackingEnabled)
			{
				$this->fields['ALLOW_TIME_TRACKING'] = 'N';
			}
		}
	}

	private function prepareStatusSummary(): void
	{
		if (!is_array($this->fields['SE_PARAMETER'] ?? null))
		{
			return;
		}

		if (
			isset($this->fields['SE_PARAMETER'][ParameterTable::PARAM_RESULT_REQUIRED])
			&& $this->fields['SE_PARAMETER'][ParameterTable::PARAM_RESULT_REQUIRED]['VALUE'] === 'Y'
		)
		{
			$taskStatusSummaryEnabled = Bitrix24::checkFeatureEnabled(
				Bitrix24\FeatureDictionary::TASK_STATUS_SUMMARY
			);
			if (!$taskStatusSummaryEnabled)
			{
				$this->fields['SE_PARAMETER'][ParameterTable::PARAM_RESULT_REQUIRED]['VALUE'] = 'N';
			}
		}
	}

	private function prepareReplication(): void
	{
		if (isset($this->fields['REPLICATE']))
		{
			$taskRecurringEnabled = Bitrix24::checkFeatureEnabled(
				Bitrix24\FeatureDictionary::TASK_RECURRING_TASKS
			);

			if ($this->fields['REPLICATE'] === true && !$taskRecurringEnabled)
			{
				$this->fields['REPLICATE'] = false;
			}
		}
	}
}
