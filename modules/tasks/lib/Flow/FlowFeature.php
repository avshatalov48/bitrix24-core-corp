<?php

namespace Bitrix\Tasks\Flow;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\DI\Container;

final class FlowFeature
{
	public const KEY = 'feature_flows_enabled';
	public const LIMIT_CODE = 'limit_tasks_flows';

	public const FEATURE_ID = 'tasks_flow';
	private const TRIAL_DAYS = 15;
	private const DEMO_KEY = 'trialable_feature_enabled';

	public static function isOn(): bool
	{
		return Container::getInstance()->getToolService()->isFlowsAvailable();
	}

	// todo: remove
	public static function isOptionEnabled(): bool
	{
		return true;
	}

	public static function isFeatureEnabled(): bool
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return true;
		}

		return Feature::isFeatureEnabled(self::FEATURE_ID);
	}

	public static function isFeatureEnabledByTrial(): bool
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return true;
		}

		return (
			Feature::isFeatureEnabled(self::FEATURE_ID)
			&& array_key_exists(self::FEATURE_ID, Feature::getTrialFeatureList())
		);
	}

	public static function canTurnOnTrial(): bool
	{
		return !self::isDemoFeatureWasEnabled();
	}

	public static function turnOnTrial(): void
	{
		Feature::setFeatureTrialable(self::FEATURE_ID, [
			'days' => self::TRIAL_DAYS,
		]);

		Feature::trialFeature(self::FEATURE_ID);

		self::setDemoOption();
	}

	private static function setDemoOption(): void
	{
		Option::set('tasks', self::DEMO_KEY, true);
	}

	private static function isDemoFeatureWasEnabled(): bool
	{
		return (bool)Option::get('tasks', self::DEMO_KEY, false);
	}
}
