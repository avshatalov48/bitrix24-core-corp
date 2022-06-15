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

	public static function get($entityTypeId, $entityId)
	{
		if (isset(static::$cache[$entityTypeId]) && isset(static::$cache[$entityTypeId][$entityId]))
		{
			return static::$cache[$entityTypeId][$entityId];
		}
		return null;
	}

	public static function set($entityTypeId, $entityId, $value)
	{
		if (!isset(static::$cache[$entityTypeId]))
		{
			static::$cache[$entityTypeId] = [];
		}
		static::$cache[$entityTypeId][$entityId] = $value;
	}
}
