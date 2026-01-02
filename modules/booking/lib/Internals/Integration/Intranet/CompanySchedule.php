<?php

namespace Bitrix\Booking\Internals\Integration\Intranet;

use Bitrix\Intranet\Portal;
use Bitrix\Intranet\Settings\ScheduleSettings;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;

class CompanySchedule
{
	public static function isScheduleSettingsAvailable(): bool
	{
		return (
			CurrentUser::get()->isAdmin()
			&& self::isAvailable()
			&& ScheduleSettings::isAvailable()
		);
	}

	public static function getScheduleSettingsUrl(): string
	{
		if (!self::isAvailable())
		{
			return '';
		}

		return Portal::getInstance()->getSettings()->getSettingsUrl() . '?page=schedule';
	}

	private static function isAvailable(): bool
	{
		return Loader::includeModule('intranet');
	}
}
