<?php

namespace Bitrix\Im\Update;

use Bitrix\Main\Data\Cache;

class Settings
{
	private const CACHE_DIR = '/im/option/';
	public static function cleanCacheAgent()
	{
		$cache = Cache::createInstance();
		$cache->cleanDir(self::CACHE_DIR);


		return '';
	}
}