<?php
namespace Bitrix\Crm\Integration\Channel;

use Bitrix\Main;
use Bitrix\Crm\Integration\Channel\Entity\EntityChannelTable;

class EntityChannelBinding
{
	/**
	 * Get all channel bindings for specified entity.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getAll($entityTypeID, $entityID)
	{
		return EntityChannelTable::getBindings($entityTypeID, $entityID);
	}
	/**
	 * Check if entity has channel bindings.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function exists($entityTypeID, $entityID)
	{
		return EntityChannelTable::hasBindings($entityTypeID, $entityID);
	}
	/**
	 * Register binding to the channel.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @param ChannelType $typeID Channel Type ID.
	 * @param array $params Array of binding parameters. For example ORIGIN_ID and COMPONENT_ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function register($entityTypeID, $entityID, $typeID, array $params = null)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$binding = array('TYPE_ID' => $typeID);
		if(isset($params['ORIGIN_ID']))
		{
			$binding['ORIGIN_ID'] = $params['ORIGIN_ID'];
		}
		if(isset($params['COMPONENT_ID']))
		{
			$binding['COMPONENT_ID'] = $params['COMPONENT_ID'];
		}

		EntityChannelTable::bind($entityTypeID, $entityID, array($binding));
	}
	/**
	 * Unregister binding to the channel.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @param array $bindings Array of channel bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unregister($entityTypeID, $entityID, array $bindings)
	{
		EntityChannelTable::unbind($entityTypeID, $entityID, $bindings);
	}
	/**
	 * Unregister bindings to all channels.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unregisterAll($entityTypeID, $entityID)
	{
		EntityChannelTable::unbindAll($entityTypeID, $entityID);
	}
}