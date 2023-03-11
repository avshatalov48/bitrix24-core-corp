<?php
namespace Bitrix\ImOpenLines\Widget;

use Bitrix\Main\Application;

class Cache
{
	static $cacheDir = '/bx/imol/widget/cache/';
	static $cacheTtl = 8*60*60; // 8 hour

	public static function get(int $userId, ?string $option = null)
	{
		$cache = \Bitrix\Main\Data\Cache::createInstance();

		$result = [];
		if ($cache->initCache(self::$cacheTtl, $userId, self::$cacheDir))
		{
			$result = $cache->getVars();
		}

		if ($option !== null)
		{
			if (
				!empty($result)
				&& is_array($result)
				&& isset($result[$option])
			)
			{
				return $result[$option];
			}
			else
			{
				return null;
			}
		}

		return $result;
	}

	public static function set(int $userId, array $params): bool
	{
		$cache = \Bitrix\Main\Data\Cache::createInstance();

		$result = [];

		if (!empty($params))
		{
			if ($cache->initCache(self::$cacheTtl, $userId, self::$cacheDir))
			{
				$result = $cache->getVars();
			}

			foreach ($params as $name => $value)
			{
				if ($value === '')
				{
					unset($result[$name]);
				}
				else
				{
					$result[$name] = $value;
				}
			}
		}

		$cache->forceRewriting(true);
		$cache->startDataCache(self::$cacheTtl, $userId, self::$cacheDir);
		$cache->endDataCache($result);

		return true;
	}
}