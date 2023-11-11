<?php

namespace Bitrix\ImMobile;

class Settings
{
	public static function isBetaAvailable(): bool
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return false;
		}

		return \Bitrix\Im\Settings::isBetaAvailable();
	}
}
