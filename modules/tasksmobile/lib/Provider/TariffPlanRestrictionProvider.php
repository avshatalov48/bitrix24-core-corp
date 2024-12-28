<?php

namespace Bitrix\TasksMobile\Provider;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\FilterLimit;
use Bitrix\Tasks\Util\UserField\Restriction;
use Bitrix\Tasks\Util\UserField\Task;

final class TariffPlanRestrictionProvider
{
	/**
	 * Handler for mobile event onTariffRestrictionsCollect
	 *
	 * @return EventResult
	 */
	public static function getTariffPlanRestrictions(): EventResult
	{
		return new EventResult(
			EventResult::SUCCESS,
			[
				'restrictions' => [
					FlowFeature::FEATURE_ID => [
						'code' => FlowFeature::FEATURE_ID,
						'title' => Loc::getMessage('TASKSMOBILE_TARIFF_PLAN_RESTRICTION_TASKS_FLOW'),
						'isRestricted' => self::isFlowRestricted(),
						'isPromo' => self::isFlowPromo(),
					],
					FeatureDictionary::TASK_EFFICIENCY => [
						'code' => FeatureDictionary::TASK_EFFICIENCY,
						'title' => Loc::getMessage('TASKSMOBILE_TARIFF_PLAN_RESTRICTION_TASKS_EFFICIENCY'),
						'isRestricted' => self::isEfficiencyRestricted(),
						'isPromo' => self::isEfficiencyPromo(),
					],
					FeatureDictionary::TASK_DELEGATING => [
						'code' => FeatureDictionary::TASK_DELEGATING,
						'title' => Loc::getMessage('TASKSMOBILE_TARIFF_PLAN_RESTRICTION_TASKS_DELEGATING'),
						'isRestricted' => self::isDelegatingRestricted(),
						'isPromo' => self::isDelegatingPromo(),
					],
					FeatureDictionary::TASK_OBSERVERS_PARTICIPANTS => [
						'code' => FeatureDictionary::TASK_OBSERVERS_PARTICIPANTS,
						'title' => Loc::getMessage('TASKSMOBILE_TARIFF_PLAN_RESTRICTION_TASKS_ACCOMPLICE_AUDITOR'),
						'isRestricted' => self::isAccompliceAuditorRestricted(),
						'isPromo' => self::isAccompliceAuditorPromo(),
					],
					FeatureDictionary::TASK_CRM_INTEGRATION => [
						'code' => FeatureDictionary::TASK_CRM_INTEGRATION,
						'title' => Loc::getMessage('TASKSMOBILE_TARIFF_PLAN_RESTRICTION_TASKS_CRM'),
						'isRestricted' => self::isCrmRestricted(),
						'isPromo' => self::isCrmPromo(),
					],
					FeatureDictionary::TASK_TIME_TRACKING => [
						'code' => FeatureDictionary::TASK_TIME_TRACKING,
						'title' => Loc::getMessage('TASKSMOBILE_TARIFF_PLAN_RESTRICTION_TIME_TRACKING'),
						'isRestricted' => self::isTimeTrackingRestricted(),
						'isPromo' => self::isTimeTrackingPromo(),
					],
					FeatureDictionary::TASK_STATUS_SUMMARY => [
						'code' => FeatureDictionary::TASK_STATUS_SUMMARY,
						'title' => Loc::getMessage('TASKSMOBILE_TARIFF_PLAN_RESTRICTION_RESULT_REQUIREMENT'),
						'isRestricted' => self::isResultRequirementRestricted(),
						'isPromo' => self::isResultRequirementPromo(),
					],
					FeatureDictionary::TASK_CONTROL => [
						'code' => FeatureDictionary::TASK_CONTROL,
						'title' => Loc::getMessage('TASKSMOBILE_TARIFF_PLAN_RESTRICTION_TASK_CONTROL'),
						'isRestricted' => self::isTaskControlRestricted(),
						'isPromo' => self::isTaskControlPromo(),
					],
					FeatureDictionary::TASK_SKIP_WEEKENDS => [
						'code' => FeatureDictionary::TASK_SKIP_WEEKENDS,
						'title' => Loc::getMessage('TASKSMOBILE_TARIFF_PLAN_RESTRICTION_WORK_TIME_MATCH'),
						'isRestricted' => self::isWorkTimeMatchRestricted(),
						'isPromo' => self::isWorkTimeMatchPromo(),
					],
					FeatureDictionary::TASK_CUSTOM_FIELDS => [
						'code' => FeatureDictionary::TASK_CUSTOM_FIELDS,
						'title' => Loc::getMessage('TASKSMOBILE_TARIFF_PLAN_RESTRICTION_USER_FIELDS'),
						'isRestricted' => self::isUserFieldRestricted(),
						'isPromo' => self::isUserFieldPromo(),
					],
					'tasks_search' => [
						'code' => 'tasks_search',
						'title' => Loc::getMessage('TASKSMOBILE_TARIFF_PLAN_RESTRICTION_SEARCH'),
						'isRestricted' => self::isSearchRestricted(),
						'isPromo' => false,
					],
				],
			],
			'tasksmobile',
		);
	}

	public static function isFlowRestricted(): bool
	{
		return !FlowFeature::isFeatureEnabled() && !FlowFeature::isFeatureEnabledByTrial();
	}

	public static function isEfficiencyRestricted(): bool
	{
		return !Bitrix24::checkFeatureEnabled(FeatureDictionary::TASK_EFFICIENCY);
	}

	public static function isDelegatingRestricted(): bool
	{
		return !Bitrix24::checkFeatureEnabled(FeatureDictionary::TASK_DELEGATING);
	}

	public static function isAccompliceAuditorRestricted(): bool
	{
		return !Bitrix24::checkFeatureEnabled(FeatureDictionary::TASK_OBSERVERS_PARTICIPANTS);
	}

	public static function isCrmRestricted(): bool
	{
		return !Bitrix24::checkFeatureEnabled(FeatureDictionary::TASK_CRM_INTEGRATION);
	}

	public static function isTimeTrackingRestricted(): bool
	{
		return !Bitrix24::checkFeatureEnabled(FeatureDictionary::TASK_TIME_TRACKING);
	}

	public static function isResultRequirementRestricted(): bool
	{
		return !Bitrix24::checkFeatureEnabled(FeatureDictionary::TASK_STATUS_SUMMARY);
	}

	public static function isTaskControlRestricted(): bool
	{
		return !Bitrix24::checkFeatureEnabled(FeatureDictionary::TASK_CONTROL);
	}

	public static function isWorkTimeMatchRestricted(): bool
	{
		return !Bitrix24::checkFeatureEnabled(FeatureDictionary::TASK_SKIP_WEEKENDS);
	}

	public static function isUserFieldRestricted(): bool
	{
		return !Restriction::canUse(Task::getEntityCode());
	}

	public static function isSearchRestricted(): bool
	{
		return FilterLimit::isLimitExceeded();
	}

	public static function isFlowPromo(): bool
	{
		return self::isFeaturePromo(FlowFeature::FEATURE_ID);
	}

	public static function isEfficiencyPromo(): bool
	{
		return self::isFeaturePromo(FeatureDictionary::TASK_EFFICIENCY);
	}

	public static function isDelegatingPromo(): bool
	{
		return self::isFeaturePromo(FeatureDictionary::TASK_DELEGATING);
	}

	public static function isAccompliceAuditorPromo(): bool
	{
		return self::isFeaturePromo(FeatureDictionary::TASK_OBSERVERS_PARTICIPANTS);
	}

	public static function isCrmPromo(): bool
	{
		return self::isFeaturePromo(FeatureDictionary::TASK_CRM_INTEGRATION);
	}

	public static function isTimeTrackingPromo(): bool
	{
		return self::isFeaturePromo(FeatureDictionary::TASK_TIME_TRACKING);
	}

	public static function isResultRequirementPromo(): bool
	{
		return self::isFeaturePromo(FeatureDictionary::TASK_STATUS_SUMMARY);
	}

	public static function isTaskControlPromo(): bool
	{
		return self::isFeaturePromo(FeatureDictionary::TASK_CONTROL);
	}

	public static function isWorkTimeMatchPromo(): bool
	{
		return self::isFeaturePromo(FeatureDictionary::TASK_SKIP_WEEKENDS);
	}

	public static function isUserFieldPromo(): bool
	{
		return self::isFeaturePromo(FeatureDictionary::TASK_CUSTOM_FIELDS);
	}

	private static function isFeaturePromo(string $featureId): bool
	{
		return Loader::includeModule('bitrix24') && Feature::isPromoEditionAvailableByFeature($featureId);
	}
}
