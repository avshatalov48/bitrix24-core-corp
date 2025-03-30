<?php

namespace Bitrix\Crm\Integration\AI;

use Bitrix\Crm\Integration\AI\Operation\FillItemFieldsFromCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\Orchestrator;
use Bitrix\Crm\Integration\AI\Operation\Scenario;
use Bitrix\Crm\Integration\AI\Operation\ScoreCall;
use Bitrix\Crm\Integration\AI\Operation\SummarizeCallTranscription;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MultiValueStoreService;
use CCrmOwnerType;

final class CopilotLauncher
{
	private readonly ?Result $transcriptionResult;
	private readonly ?Result $callScoringResult;
	private readonly ?Result $summarizeResult;
	private readonly ?Result $fillResult;

	private readonly ?ItemIdentifier $fillTarget;

	public function __construct(
		private readonly int $activityId,
		private readonly int $userId,
		private readonly string $scenario,
	)
	{
		$jobRepo = JobRepository::getInstance();

		$this->transcriptionResult = $jobRepo->getTranscribeCallRecordingResultByActivity($activityId);
		$this->summarizeResult = $jobRepo->getSummarizeCallTranscriptionResultByActivity($activityId);

		$this->fillTarget = (new Orchestrator())->findPossibleFillFieldsTarget($activityId);
		if ($this->fillTarget)
		{
			$this->fillResult = $jobRepo->getFillItemFieldsFromCallTranscriptionResult($this->fillTarget, $activityId);
		}

		$this->callScoringResult = $jobRepo->getCallScoringResult($activityId);
	}

	public function run(): ?Result
	{
		return match ($this->scenario)
		{
			Scenario::FILL_FIELDS_SCENARIO => $this->runFillFieldsScenario(),
			Scenario::CALL_SCORING_SCENARIO => $this->runCallScoringScenario(),
			default => $this->runFullScenario(),
		};
	}

	// region Scenarios
	public function runFillFieldsScenario(): ?Result
	{
		if ($this->transcriptionResult?->isPending())
		{
			return $this->transcriptionResult;
		}

		if (!$this->transcriptionResult?->isSuccess())
		{
			return AIManager::launchCallRecordingTranscription($this->activityId, $this->scenario);
		}

		if ($this->summarizeResult?->isPending())
		{
			return $this->summarizeResult;
		}

		if (!$this->summarizeResult?->isSuccess())
		{
			$operation = new SummarizeCallTranscription(
				new ItemIdentifier(CCrmOwnerType::Activity, $this->activityId),
				$this->transcriptionResult->getPayload()->transcription,
				$this->userId,
				$this->transcriptionResult->getJobId(),
			);

			return $operation->launch();
		}

		if (!$this->fillTarget)
		{
			return (new Result(FillItemFieldsFromCallTranscription::TYPE_ID))->addError(
				ErrorCode::getNotFoundError(),
			);
		}

		if ($this->fillResult?->isPending())
		{
			return $this->fillResult;
		}

		if (!$this->fillResult?->isSuccess())
		{
			$operation = new FillItemFieldsFromCallTranscription(
				$this->fillTarget,
				$this->summarizeResult->getPayload()->summary,
				$this->userId,
				$this->summarizeResult->getJobId(),
			);

			return $operation->launch();
		}

		return $this->fillResult;
	}

	public function runCallScoringScenario(?int $assessmentSettingsId = null): ?Result
	{
		if ($this->transcriptionResult?->isPending())
		{
			return $this->transcriptionResult;
		}

		if (!$this->transcriptionResult?->isSuccess())
		{
			$result = AIManager::launchCallRecordingTranscription($this->activityId, $this->scenario);
			if ($assessmentSettingsId !== null)
			{
				$key = ScoreCall::generateJobCallAssessmentBindKey($result->getJobId(), $this->activityId);
				MultiValueStoreService::getInstance()->set($key, $assessmentSettingsId);
			}

			return $result;
		}

		if ($this->callScoringResult?->isPending())
		{
			return $this->callScoringResult;
		}

		if ($assessmentSettingsId > 0 || !$this->callScoringResult?->isSuccess())
		{
			$operation = new ScoreCall(
				new ItemIdentifier(CCrmOwnerType::Activity, $this->activityId),
				$this->transcriptionResult->getPayload()->transcription,
				$this->transcriptionResult->getUserId() ?? $this->userId,
				$this->transcriptionResult->getJobId(),
				$assessmentSettingsId
			);

			return $operation->launch();
		}

		return $this->callScoringResult;
	}

	public function runFullScenario(): ?Result
	{
		$result = $this->runFillFieldsScenario();
		if (!$result?->isPending())
		{
			return $this->runCallScoringScenario();
		}

		return $result;
	}
	// endregion
}
