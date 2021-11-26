<?php

namespace Bitrix\Crm\Settings;

use Bitrix\Main\Loader;

class Crm
{
	private const OPTION_MODULE = 'crm';
	private const OPTION_NAME = 'WAS_INITED';

	public static function wasInitiated(): bool
	{
		return (bool)\Bitrix\Main\Config\Option::get(self::OPTION_MODULE, self::OPTION_NAME, false);
	}

	public static function markAsInitiated(): void
	{
		if (!self::wasInitiated())
		{
			\Bitrix\Main\Config\Option::set(self::OPTION_MODULE, self::OPTION_NAME, true);
			$GLOBALS['CACHE_MANAGER']->ClearByTag('crm_initiated');
			\Bitrix\Crm\Integration\PullManager::getInstance()->sendCrmInitiatedEvent();
		}
	}
}
