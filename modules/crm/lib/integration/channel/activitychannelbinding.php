<?php
namespace Bitrix\Crm\Integration\Channel;

use Bitrix\Main;
use Bitrix\Crm\Statistics\ActivityChannelStatisticEntry;

/**
 * Class ActivityChannelBinding
 * Managing of Activity bindings to external channels.
 * @package Bitrix\Crm\Integration\Channel
 */
class ActivityChannelBinding
{
	/**
	 * Get all bindings to channels for specified Activity.
	 * @param int $ID Activity ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getAll($ID)
	{
		return EntityChannelBinding::getAll(\CCrmOwnerType::Activity, $ID);
	}
	/**
	 * Check if specified Activity has bindings to channel.
	 * @param int $ID Activity ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function exists($ID)
	{
		return EntityChannelBinding::exists(\CCrmOwnerType::Activity, $ID);
	}
	/**
	 * Register binding to the channel for specified Activity.
	 * @param int $ID Activity ID.
	 * @param ChannelType $typeID Channel Type ID.
	 * @param array $params Array of binding parameters. For example ORIGIN_ID and COMPONENT_ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function register($ID, $typeID, array $params = null)
	{
		EntityChannelBinding::register(\CCrmOwnerType::Activity, $ID, $typeID, $params);
		ActivityChannelStatisticEntry::register($ID, $typeID, $params);
	}
	/**
	 * Synchronize Activity statistics.
	 * @param int $ID Activity ID
	 * @param array $fields Activity Fields
	 * @throws Main\ArgumentException
	 */
	public static function synchronize($ID, array $fields)
	{
		foreach(self::getAll($ID) as $binding)
		{
			$typeID = isset($binding['TYPE_ID']) ? (int)$binding['TYPE_ID'] : ChannelType::UNDEFINED;
			if(ChannelType::isDefined($typeID))
			{
				ActivityChannelStatisticEntry::register($ID, $typeID, $binding, $fields);
			}
		}
	}
	/**
	 * Unregister binding to the channel for specified Activity.
	 * @param int $ID Activity ID.
	 * @param ChannelType $typeID Channel Type ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unregister($ID, $typeID)
	{
		EntityChannelBinding::unregister(\CCrmOwnerType::Activity, $ID, array(array('TYPE_ID' => $typeID)));
		ActivityChannelStatisticEntry::unregister($ID, $typeID);
	}
	/**
	 * Unregister bindings to all channels for specified Activity.
	 * @param int $ID Activity ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unregisterAll($ID)
	{
		EntityChannelBinding::unregisterAll(\CCrmOwnerType::Activity, $ID);
		ActivityChannelStatisticEntry::unregister($ID);
	}
}