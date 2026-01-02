<?php

namespace Bitrix\Timeman\Integration\StaffTrack;

use Bitrix\Main\Loader;
use Bitrix\StaffTrack\Feature;

class CheckIn
{
	public static function isEnabled(): bool
	{
		if (!Loader::includeModule('stafftrack'))
		{
			return false;
		}

		return Feature::isCheckInEnabled() && Feature::isCheckInEnabledBySettings();
	}

	public static function isCheckInStartEnabled(): bool
	{
		return self::isEnabled() && Feature::isCheckInStartEnabled();
	}
}
