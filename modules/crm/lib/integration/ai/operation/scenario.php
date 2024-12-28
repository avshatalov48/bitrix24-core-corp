<?php

namespace Bitrix\Crm\Integration\AI\Operation;

use Bitrix\Crm\Feature;
use Bitrix\Crm\Feature\CopilotInCallGrading;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Enum\GlobalSetting;

final class Scenario
{
	public const UNDEFINED_SCENARIO = '';
	public const FULL_SCENARIO = 'full';
	public const FILL_FIELDS_SCENARIO = 'fill_fields';
	public const CALL_SCORING_SCENARIO = 'call_scoring';
	public const EXTRACT_SCORING_CRITERIA_SCENARIO = 'extract_scoring_criteria';

	public static function isSupportedScenario(string $scenario): bool
	{
		$scenarioList = [
			self::FULL_SCENARIO,
			self::FILL_FIELDS_SCENARIO,
		];

		if (self::isMultiScenarioEnabled())
		{
			$scenarioList[] = self::CALL_SCORING_SCENARIO;
			$scenarioList[] = self::EXTRACT_SCORING_CRITERIA_SCENARIO;
		}

		return in_array($scenario, $scenarioList, true);
	}

	public static function isEnabledScenario(string $scenario): bool
	{
		if (!self::isSupportedScenario($scenario))
		{
			return false;
		}

		$isFillFieldsEnabled = AIManager::isEnabledInGlobalSettings();
		if (!self::isMultiScenarioEnabled())
		{
			return $isFillFieldsEnabled; // only fill fields scenario when multi scenario disabled
		}

		$isScoreCallEnabled = AIManager::isEnabledInGlobalSettings(GlobalSetting::CallAssessment);

		return $isFillFieldsEnabled || $isScoreCallEnabled;
	}

	public static function filterScenarioByGlobalSettings(string $scenario): ?string
	{
		if (!self::isEnabledScenario($scenario))
		{
			return null;
		}

		if (!self::isMultiScenarioEnabled())
		{
			return $scenario;
		}

		if (!AIManager::isEnabledInGlobalSettings())
		{
			return match ($scenario) {
				self::CALL_SCORING_SCENARIO => self::CALL_SCORING_SCENARIO,
				default => null,
			};
		}

		if (!AIManager::isEnabledInGlobalSettings(GlobalSetting::CallAssessment))
		{
			return match ($scenario) {
				self::FILL_FIELDS_SCENARIO => self::FILL_FIELDS_SCENARIO,
				default => null,
			};
		}

		return $scenario;
	}

	public static function getNextTypeIdByScenario(?string $scenario): ?int
	{
		if ($scenario === self::FILL_FIELDS_SCENARIO)
		{
			return SummarizeCallTranscription::TYPE_ID;
		}

		if ($scenario === self::CALL_SCORING_SCENARIO)
		{
			return ScoreCall::TYPE_ID;
		}

		return null; // FULL_SCENARIO
	}

	public static function isMultiScenarioEnabled(?int $timestamp = null): bool
	{
		$isFeatureEnabled = Feature::enabled(CopilotInCallGrading::class);

		if (isset($timestamp))
		{
			return $isFeatureEnabled && $timestamp > CopilotInCallGrading::getCopilotInCallGradingTs();
		}

		return $isFeatureEnabled;
	}
}
