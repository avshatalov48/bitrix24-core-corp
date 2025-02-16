<?php

namespace Bitrix\Crm\Integration\AI\Operation;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\ItemIdentifier;
use CCrmActivity;

final class OperationState
{
	private ?Result $transcriptionResult;
	private ?Result $callScoringResult;
	private ?Result $summarizeResult;
	private ?Result $fillResult;

	public function __construct(
		private readonly int $activityId,
		private readonly int $entityTypeId,
		private readonly int $entityId
	)
	{
		$this->transcriptionResult = null;
		$this->callScoringResult = null;
		$this->summarizeResult = null;
		$this->fillResult = null;

		$this->init();
	}

	// region FullScenario
	public function isLaunchOperationsPending(): bool
	{
		if (!$this->isValidParams())
		{
			return false;
		}

		return $this->transcriptionResult?->isPending()
			|| $this->summarizeResult?->isPending()
			|| $this->fillResult?->isPending()
			|| $this->callScoringResult?->isPending()
		;
	}

	public function isLaunchOperationsSuccess(bool $checkBindings = true): bool
	{
		if (!$this->isValidParams())
		{
			return false;
		}

		if ($checkBindings)
		{
			$bindings = $this->fetchEntityBindings();
			foreach ($bindings as $binding)
			{
				if (
					(new self($binding['OWNER_TYPE_ID'], $binding['OWNER_ID'], $this->activityId))
						->isLaunchOperationsSuccess(false)
				)
				{
					return true;
				}
			}
		}

		return $this->transcriptionResult?->isSuccess()
			&& $this->summarizeResult?->isSuccess()
			&& $this->fillResult?->isSuccess()
			&& $this->callScoringResult?->isSuccess()
		;
	}
	// endregion

	// region FillFieldsScenario
	public function isFillFieldsScenarioPending(): bool
	{
		if (!$this->isValidParams())
		{
			return false;
		}

		if (
			$this->summarizeResult?->isPending()
			|| $this->fillResult?->isPending()
		)
		{
			return true;
		}

		return $this->isFillFieldsScenario()
			&& (
				$this->transcriptionResult?->isPending()
				|| $this->summarizeResult?->isPending()
				|| $this->fillResult?->isPending()
			)
		;
	}

	public function isFillFieldsScenarioSuccess(): bool
	{
		if (!$this->isValidParams())
		{
			return false;
		}

		if ($this->fillResult?->isSuccess())
		{
			return true;
		}

		return (
			$this->isLaunchOperationsSuccess()
			|| $this->fillResult?->isSuccess()
		);
	}

	public function isFillFieldsScenarioErrorsLimitExceeded(): bool
	{
		if (!$this->isValidParams())
		{
			return true;
		}

		if (
			$this->summarizeResult?->isErrorsLimitExceeded()
			|| $this->fillResult?->isErrorsLimitExceeded()
		)
		{
			return true;
		}

		return (
			$this->isFillFieldsScenario()
			&& (
				$this->transcriptionResult?->isErrorsLimitExceeded()
				|| $this->summarizeResult?->isErrorsLimitExceeded()
				|| $this->fillResult?->isErrorsLimitExceeded()
			)
		);
	}
	// endregion

	// region ScoreCallScenario
	public function isCallScoringScenarioPending(): bool
	{
		if (!$this->isValidParams())
		{
			return false;
		}

		if ($this->callScoringResult?->isPending())
		{
			return true;
		}

		return $this->isCallScoringScenario()
			&& (
				$this->transcriptionResult?->isPending()
				|| $this->callScoringResult?->isPending()
			)
		;
	}

	public function isCallScoringScenarioSuccess(): bool
	{
		if (!$this->isValidParams())
		{
			return false;
		}

		if ($this->callScoringResult?->isSuccess())
		{
			return true;
		}

		return (
			$this->isLaunchOperationsSuccess()
			|| $this->callScoringResult?->isSuccess()
		);
	}

	public function isCallScoringScenarioErrorsLimitExceeded(): bool
	{
		if (!$this->isValidParams())
		{
			return true;
		}

		if ($this->callScoringResult?->isErrorsLimitExceeded())
		{
			return true;
		}

		return $this->isCallScoringScenario()
			&& (
				$this->transcriptionResult?->isErrorsLimitExceeded()
				|| $this->callScoringResult?->isErrorsLimitExceeded()
			)
		;
	}
	// endregion

	// region Utils
	private function init(bool $isCacheReset = true): void
	{
		$jobRepo = JobRepository::getInstance();
		if ($isCacheReset)
		{
			$jobRepo->cleanRuntimeCache();
		}

		$this->transcriptionResult = $jobRepo->getTranscribeCallRecordingResultByActivity($this->activityId);
		$this->summarizeResult = $jobRepo->getSummarizeCallTranscriptionResultByActivity($this->activityId);
		$this->callScoringResult = $jobRepo->getCallScoringResult($this->activityId);

		if ($this->isValidParams())
		{
			$this->fillResult = $jobRepo->getFillItemFieldsFromCallTranscriptionResult(
				new ItemIdentifier($this->entityTypeId, $this->entityId),
				$this->activityId
			);
		}
	}

	private function isFillFieldsScenario(): bool
	{
		return $this->transcriptionResult?->getNextTypeId() === null
			|| $this->transcriptionResult?->getNextTypeId() === SummarizeCallTranscription::TYPE_ID
		;
	}

	private function isCallScoringScenario(): bool
	{
		return $this->transcriptionResult?->getNextTypeId() === ScoreCall::TYPE_ID;
	}

	private function isValidParams(): bool
	{
		return $this->activityId >= 0
			&& in_array($this->entityTypeId, AIManager::SUPPORTED_ENTITY_TYPE_IDS, true)
		;
	}

	private function fetchEntityBindings(): array
	{
		$bindings = CCrmActivity::GetBindings($this->activityId);
		$bindings = is_array($bindings) ? $bindings : [];

		return array_filter(
			$bindings,
			fn(array $row) => in_array(
				(int)$row['OWNER_TYPE_ID'],
				AIManager::SUPPORTED_ENTITY_TYPE_IDS,
				true
			) && $this->entityTypeId !== (int)$row['OWNER_TYPE_ID']
		);
	}
	// endregion
}
