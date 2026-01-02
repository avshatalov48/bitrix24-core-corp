<?php

namespace Bitrix\Call;

use Bitrix\Main\Data\Cache;

class Idempotence
{
	private static $cacheTtl = 86400;
	private static $cacheDir = '/call/idempotence';

	/**
	 * @param string $key
	 * @return bool
	 */
	public static function addKey(string $key): bool
	{
		$cache = Cache::createInstance();
		$cacheKey = self::getCacheKey($key);

		if ($cache->startDataCache(self::$cacheTtl, $cacheKey, self::$cacheDir))
		{
			$cache->endDataCache(['key' => $key]);
			return true;
		}

		return false;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public static function isUnique(string $key): bool
	{
		$cache = Cache::createInstance();
		$cacheKey = self::getCacheKey($key);

		if ($cache->initCache(self::$cacheTtl, $cacheKey, self::$cacheDir))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param string $key
	 * @return string
	 */
	private static function getCacheKey(string $key): string
	{
		return md5($key);
	}
}
