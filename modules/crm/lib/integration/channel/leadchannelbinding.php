<?php
namespace Bitrix\Crm\Integration\Channel;

use Bitrix\Main;
use Bitrix\Crm\Statistics\LeadChannelStatisticEntry;

/**
 * Class LeadChannelBinding
 * Managing of Lead bindings to external channels.
 * @package Bitrix\Crm\Integration\Channel
 */
class LeadChannelBinding
{
	/**
	 * Get all bindings to channels for specified Lead.
	 * @param int $ID Lead ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getAll($ID)
	{
		return EntityChannelBinding::getAll(\CCrmOwnerType::Lead, $ID);
	}
	/**
	 * Check if specified Lead has bindings to channel.
	 * @param int $ID Lead ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function exists($ID)
	{
		return EntityChannelBinding::exists(\CCrmOwnerType::Lead, $ID);
	}
	/**
	 * Register binding to the channel for specified Lead.
	 * @param int $ID Lead ID.
	 * @param ChannelType $typeID Channel Type ID.
	 * @param array $params Array of binding parameters. For example ORIGIN_ID and COMPONENT_ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function register($ID, $typeID, array $params = null)
	{
		EntityChannelBinding::register(\CCrmOwnerType::Lead, $ID, $typeID, $params);
		LeadChannelStatisticEntry::register($ID, $typeID, $params);
	}
	/**
	 * Synchronize Lead statistics.
	 * @param int $ID Lead ID
	 * @param array $fields Lead Fields
	 * @throws Main\ArgumentException
	 */
	public static function synchronize($ID, array $fields)
	{
		foreach(self::getAll($ID) as $binding)
		{
			$typeID = isset($binding['TYPE_ID']) ? (int)$binding['TYPE_ID'] : ChannelType::UNDEFINED;
			if(ChannelType::isDefined($typeID))
			{
				LeadChannelStatisticEntry::register($ID, $typeID, $binding, $fields);
			}
		}
	}
	/**
	 * Unregister binding to the channel for specified Lead.
	 * @param int $ID Lead ID.
	 * @param ChannelType $typeID Channel Type ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unregister($ID, $typeID)
	{
		EntityChannelBinding::unregister(\CCrmOwnerType::Lead, $ID, array(array('TYPE_ID' => $typeID)));
		LeadChannelStatisticEntry::unregister($ID, $typeID);
	}
	/**
	 * Unregister bindings to all channels for specified Lead.
	 * @param int $ID Lead ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unregisterAll($ID)
	{
		EntityChannelBinding::unregisterAll(\CCrmOwnerType::Lead, $ID);
		LeadChannelStatisticEntry::unregister($ID);
	}
}