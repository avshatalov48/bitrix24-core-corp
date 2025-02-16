<?php

namespace Bitrix\Crm\Integration\AI\Operation;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Enum\GlobalSetting;

final class Scenario
{
	public const UNDEFINED_SCENARIO = '';
	public const FULL_SCENARIO = 'full';
	public const FILL_FIELDS_SCENARIO = 'fill_fields';
	public const CALL_SCORING_SCENARIO = 'call_scoring';
	public const EXTRACT_SCORING_CRITERIA_SCENARIO = 'extract_scoring_criteria';

	public const FULL_OFF_SLIDER_CODE = 'limit_copilot_off';
	public const FILL_FIELDS_SCENARIO_OFF_SLIDER_CODE = 'limit_v2_crm_copilot_fill_item_from_call_off';
	public const CALL_SCORING_SCENARIO_SLIDER_CODE = 'limit_v2_crm_copilot_call_assessment_off';

	public const SLIDER_CODE_MAP = [
		self::FULL_SCENARIO => self::FULL_OFF_SLIDER_CODE,
		self::FILL_FIELDS_SCENARIO => self::FILL_FIELDS_SCENARIO_OFF_SLIDER_CODE,
		self::CALL_SCORING_SCENARIO => self::CALL_SCORING_SCENARIO_SLIDER_CODE,
	];

	public static function isSupportedScenario(string $scenario): bool
	{
		$scenarioList = [
			self::FULL_SCENARIO,
			self::FILL_FIELDS_SCENARIO,
			self::CALL_SCORING_SCENARIO,
			self::EXTRACT_SCORING_CRITERIA_SCENARIO
		];

		return in_array($scenario, $scenarioList, true);
	}

	public static function isEnabledScenario(string $scenario): bool
	{
		if (!self::isSupportedScenario($scenario))
		{
			return false;
		}

		$isFillFieldsEnabled = AIManager::isEnabledInGlobalSettings();
		$isScoreCallEnabled = AIManager::isEnabledInGlobalSettings(GlobalSetting::CallAssessment);

		return match ($scenario)
		{
			self::FILL_FIELDS_SCENARIO => $isFillFieldsEnabled,
			self::CALL_SCORING_SCENARIO, self::EXTRACT_SCORING_CRITERIA_SCENARIO => $isScoreCallEnabled,
			self::FULL_SCENARIO => $isFillFieldsEnabled || $isScoreCallEnabled,
			default => false,
		};
	}

	public static function filterFullScenarioByGlobalSettings(string $scenario): string
	{
		if ($scenario === self::FULL_SCENARIO)
		{
			if (
				self::isEnabledScenario(self::FILL_FIELDS_SCENARIO)
				&& !self::isEnabledScenario(self::CALL_SCORING_SCENARIO)
			)
			{
				return self::FILL_FIELDS_SCENARIO;
			}

			if (
				self::isEnabledScenario(self::CALL_SCORING_SCENARIO)
				&& !self::isEnabledScenario(self::FILL_FIELDS_SCENARIO)
			)
			{
				return self::CALL_SCORING_SCENARIO;
			}
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
}
