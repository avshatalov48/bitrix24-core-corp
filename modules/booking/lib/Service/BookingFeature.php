<?php

declare(strict_types=1);

namespace Bitrix\Booking\Service;

use Bitrix\Bitrix24\Feature;
use Bitrix\Bitrix24\License;
use Bitrix\Booking\Provider\NotificationsAvailabilityProvider;
use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;

final class BookingFeature
{
	private const MODULE_ID = 'booking';
	private const FEATURE_ID = 'booking';
	private const TRIAL_DAYS = 30;

	public static function isOn(): bool
	{
		return self::isOptionEnabled();
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
		$canTurnOnTrial = false;

		if (
			Loader::includeModule('bitrix24')
			&& in_array(\CBitrix24::getLicenseType(), \CBitrix24::PAID_EDITIONS, true)
		)
		{
			$canTurnOnTrial = (
				(!self::isFeatureEnabled() && !self::isTrialFeatureWasEnabled())
				&& NotificationsAvailabilityProvider::isAvailable()
				&& (Loader::includeModule('crm') && NotificationsManager::canUse())
			);
		}

		return $canTurnOnTrial;
	}

	public static function canTurnOnDemo(): bool
	{
		return (
			!self::canTurnOnTrial()
			&& Loader::includeModule('bitrix24')
			&& License::getCurrent()->getDemo()->isAvailable()
			&& in_array(\CBitrix24::getLicenseType(), \CBitrix24::BASE_EDITIONS, true)
		);
	}

	public static function turnOnTrial(): void
	{
		Feature::setFeatureTrialable(self::FEATURE_ID, [
			'days' => self::TRIAL_DAYS,
		]);

		Feature::trialFeature(self::FEATURE_ID);

		self::setTrialOption();
	}

	private static function isOptionEnabled(): bool
	{
		if (!ModuleManager::isModuleInstalled('bitrix24'))
		{
			return true;
		}

		return (bool)Option::get(self::MODULE_ID, 'feature_booking_enabled', false);
	}

	private static function setTrialOption(): void
	{
		Option::set(self::MODULE_ID, 'trialable_feature_enabled', true);
	}

	private static function isTrialFeatureWasEnabled(): bool
	{
		return (bool)Option::get(self::MODULE_ID, 'trialable_feature_enabled', false);
	}
}
