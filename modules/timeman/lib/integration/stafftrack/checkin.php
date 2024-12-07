<?php

namespace Bitrix\Timeman\Integration\Stafftrack;

use Bitrix\Main\Loader;
use Bitrix\Stafftrack\Feature;

class CheckIn
{
	static ?bool $isCheckInEnabled = null;

	public static function isEnabled(): bool
	{
		if (isset(self::$isCheckInEnabled))
		{
			return self::$isCheckInEnabled;
		}

		self::$isCheckInEnabled = Loader::includeModule('stafftrack')
			&& Feature::isCheckInEnabled()
			&& Feature::isCheckInEnabledBySettings()
		;

		return self::$isCheckInEnabled;
	}
}