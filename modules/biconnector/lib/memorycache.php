<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class MemoryCache
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

	/**
	 * Internal method to init object state.
	 *
	 * @return void
	 */
	protected static function init()
	{
		if (static::$memoryLimit === null)
		{
			static::$memoryLimit = \Bitrix\Main\Config\Ini::getInt('memory_limit') * 0.8;
		}
	}

	/**
	 * Reads the cache. Returns null if value was not saved yet.
	 *
	 * @param string $entityTypeId Cache key domain.
	 * @param string $entityId Cache key value.
	 *
	 * @return mixed|null
	 */
	public static function get($entityTypeId, $entityId)
	{
		static::init();

		if (isset(static::$cache[$entityTypeId]) && isset(static::$cache[$entityTypeId][$entityId]))
		{
			if (isset(static::$hit[$entityTypeId]))
			{
				static::$hit[$entityTypeId]++;
			}
			else
			{
				static::$hit[$entityTypeId] = 1;
			}
			return static::$cache[$entityTypeId][$entityId];
		}

		if (isset(static::$miss[$entityTypeId]))
		{
			static::$miss[$entityTypeId]++;
		}
		else
		{
			static::$miss[$entityTypeId] = 1;
		}
		return null;
	}

	/**
	 * Stores new value into the cache.
	 *
	 * @param string $entityTypeId Cache key domain.
	 * @param string $entityId Cache key value.
	 * @param mixed $value Value to be saved into cache.
	 *
	 * @return void
	 */
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

	/**
	 * Deletes least used cache entries.
	 * More and more with each run.
	 *
	 * @return void
	 */
	public static function freeMemory()
	{
		foreach (static::$cache as $entityTypeId => &$tmp)
		{
			$hit = static::$hit[$entityTypeId] ?? 0;
			$miss = static::$miss[$entityTypeId] ?? 0;
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

	/**
	 * Deletes all cache entries.
	 *
	 * @return void
	 */
	public static function expunge()
	{
		static::$cache = [];
		static::$hit = [];
		static::$miss = [];
		static::$hitRatio = 0.1;
	}
}
