<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Dictionary
 *
 * @package Bitrix\BIConnector
 **/

class MemoryCache
{
	protected static $cache = [];
	protected static $hit = [];
	protected static $miss = [];
	protected static $hitRatio = 0.1;
	protected static $memoryLimit = null;

	protected static function init()
	{
		if (static::$memoryLimit === null)
		{
			static::$memoryLimit = \Bitrix\Main\Config\Ini::getInt('memory_limit') * 0.9;
		}
	}

	public static function get($entityTypeId, $entityId)
	{
		static::init();

		if (isset(static::$cache[$entityTypeId]) && isset(static::$cache[$entityTypeId][$entityId]))
		{
			static::$hit[$entityTypeId]++;
			return static::$cache[$entityTypeId][$entityId];
		}

		static::$miss[$entityTypeId]++;
		return null;
	}

	public static function set($entityTypeId, $entityId, $value)
	{
		static::init();

		if (static::$memoryLimit && static::$memoryLimit < memory_get_usage())
		{
			self::freeMemory();
			return;
		}

		if (!isset(static::$cache[$entityTypeId]))
		{
			static::$cache[$entityTypeId] = [];
		}

		static::$cache[$entityTypeId][$entityId] = $value;
	}

	public static function freeMemory()
	{
		foreach (static::$cache as $entityTypeId => &$tmp)
		{
			$hit = isset(static::$hit[$entityTypeId]) ? static::$hit[$entityTypeId] : 0;
			$miss = isset(static::$miss[$entityTypeId]) ? static::$miss[$entityTypeId] : 0;
			if (
				($miss + $hit) == 0 //no info
				|| ($hit / ($miss + $hit)) < static::$hitRatio //or bad ratio
			)
			{
				unset(static::$cache[$entityTypeId]);
				unset(static::$hit[$entityTypeId]);
				unset(static::$miss[$entityTypeId]);
			}
		}
		unset($tmp);
		static::$hitRatio += 0.1;
	}
}
