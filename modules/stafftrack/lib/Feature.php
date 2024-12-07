<?php

namespace Bitrix\StaffTrack;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

class Feature
{
	public const MODULE_ID = 'stafftrack';
	public const CHECK_IN_SETTINGS_KEY = 'feature_check_in_enabled_by_settings';
	public const CHECK_IN_GEO_ENABLED_KEY = 'feature_check_in_geo_enabled';

	/**
	 * @return bool
	 * @throws LoaderException
	 */
	public static function isCheckInEnabled(): bool
	{
		return Loader::includeModule('mobile')
			&& Loader::includeModule('mobileapp')
			&& Loader::includeModule('stafftrackmobile')
		;
	}

	/**
	 * @return bool
	 */
	public static function isCheckInEnabledBySettings(): bool
	{
		return Option::get(self::MODULE_ID, self::CHECK_IN_SETTINGS_KEY, 'Y') === 'Y';
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function turnCheckInSettingOn(): void
	{
		Option::set(self::MODULE_ID, self::CHECK_IN_SETTINGS_KEY, 'Y');
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function turnCheckInSettingOff(): void
	{
		Option::set(self::MODULE_ID, self::CHECK_IN_SETTINGS_KEY, 'N');
	}

	/**
	 * @return bool
	 */
	public static function isCheckInGeoEnabled(): bool
	{
		$option = Option::get(self::MODULE_ID, self::CHECK_IN_GEO_ENABLED_KEY, 'default');

		if ($option === 'default')
		{
			$portalZone = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion() ?? 'en';

			return in_array($portalZone, ['ru', 'by', 'kz', 'br', 'in'], true);
		}

		return $option === 'Y';
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function turnCheckInGeoOn(): void
	{
		Option::set(self::MODULE_ID, self::CHECK_IN_GEO_ENABLED_KEY, 'Y');
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function turnCheckInGeoOff(): void
	{
		Option::set(self::MODULE_ID, self::CHECK_IN_GEO_ENABLED_KEY, 'N');
	}
}
