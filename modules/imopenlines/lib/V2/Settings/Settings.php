<?php

namespace Bitrix\ImOpenLines\V2\Settings;

use Bitrix\Im\Common;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

Loader::requireModule('im');

class Settings
{
	public static function isV2Available(): bool
	{
		if (self::isV2Activated())
		{
			return true;
		}

		$userId = Common::getUserId();
		if ($userId === false)
		{
			return false;
		}

		return \CUserOptions::GetOption('imopenlines', 'v2_activated', 'N', $userId) === 'Y';
	}

	public static function isV2Activated(): bool
	{
		return Option::get('imopenlines', 'v2_activated', 'N') === 'Y';
	}
}