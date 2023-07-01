<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\Integration\Channel\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;

/**
 * Class EntityChannelTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_EntityChannel_Query query()
 * @method static EO_EntityChannel_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_EntityChannel_Result getById($id)
 * @method static EO_EntityChannel_Result getList(array $parameters = [])
 * @method static EO_EntityChannel_Entity getEntity()
 * @method static \Bitrix\Crm\Integration\Channel\Entity\EO_EntityChannel createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Integration\Channel\Entity\EO_EntityChannel_Collection createCollection()
 * @method static \Bitrix\Crm\Integration\Channel\Entity\EO_EntityChannel wakeUpObject($row)
 * @method static \Bitrix\Crm\Integration\Channel\Entity\EO_EntityChannel_Collection wakeUpCollection($rows)
 */
class EntityChannelTable extends Entity\DataManager
{
	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_entity_channel';
	}
	/**
	 * Get table fields map.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ENTITY_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'ENTITY_TYPE_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'TYPE_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'ORIGIN_ID' => array('data_type' => 'string'),
			'COMPONENT_ID' => array('data_type' => 'string')
		);
	}
	/**
	 * Execute UPSERT operation.
	 * @param array $data Field data.
	 * @return void
	 */
	public static function upsert(array $data)
	{
		$entityID = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must contains ENTITY_ID field.', 'data');
		}

		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : 0;
		if($entityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must contains ENTITY_TYPE_ID field.', 'data');
		}

		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : 0;
		if($typeID <= 0)
		{
			throw new Main\ArgumentException('Must contains TYPE_ID field.', 'data');
		}

		$originID = isset($data['ORIGIN_ID']) ? $data['ORIGIN_ID'] : '';
		$componentID = isset($data['COMPONENT_ID']) ? $data['COMPONENT_ID'] : '';

		$connection = Main\Application::getConnection();

		$fields = array('ORIGIN_ID' => $originID, 'COMPONENT_ID' => $componentID);
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_entity_channel',
			array('ENTITY_ID', 'ENTITY_TYPE_ID', 'TYPE_ID'),
			array_merge(
				$fields,
				array('ENTITY_ID' => $entityID, 'ENTITY_TYPE_ID' => $entityTypeID, 'TYPE_ID' => $typeID)
			),
			$fields
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
	/**
	 * Get channel bindings for specified entity.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getBindings($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if($entityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		$dbResult =  Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT TYPE_ID, ORIGIN_ID, COMPONENT_ID FROM b_crm_entity_channel WHERE ENTITY_ID = {$entityID} AND ENTITY_TYPE_ID = {$entityTypeID}"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$ary['TYPE_ID'] = (int)$ary['TYPE_ID'];
			$results[] = $ary;
		}
		return $results;
	}
	/**
	 * Get channel bindings for specified entity.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @param int $typeID Type ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getBindingByType($entityTypeID, $entityID, $typeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if($entityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}
		if($typeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'typeID');
		}

		$dbResult =  Main\Application::getConnection()->query(
		/** @lang text*/
			"SELECT TYPE_ID, ORIGIN_ID, COMPONENT_ID FROM b_crm_entity_channel WHERE ENTITY_ID = {$entityID} AND ENTITY_TYPE_ID = {$entityTypeID} AND TYPE_ID = {$typeID}"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$ary['TYPE_ID'] = (int)$ary['TYPE_ID'];
			$results[] = $ary;
		}
		return $results;
	}
	/**
	 * Check if entity has channel bindings.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function hasBindings($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if($entityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		$result = self::getList(
			array(
				'select' => array('TYPE_ID'),
				'filter' => array('=ENTITY_TYPE_ID' => $entityTypeID, '=ENTITY_ID' => $entityID),
				'limit' => 1
			)
		);

		return is_array($result->fetch());
	}
	/**
	 * Bind entity to channels.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @param array $bindings Array of channel bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function bind($entityTypeID, $entityID, array $bindings)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if($entityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		$qty = count($bindings);
		if($qty === 0)
		{
			return;
		}

		for($i = 0; $i < $qty; $i++)
		{
			$binding = $bindings[$i];
			if(!is_array($binding))
			{
				continue;
			}

			$typeID = isset($binding['TYPE_ID']) ? (int)$binding['TYPE_ID'] : 0;
			if($typeID <= 0)
			{
				continue;
			}

			self::upsert(
				array(
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID,
					'TYPE_ID' => $typeID,
					'ORIGIN_ID' => isset($binding['ORIGIN_ID']) ? $binding['ORIGIN_ID'] : '',
					'COMPONENT_ID' => isset($binding['COMPONENT_ID']) ? $binding['COMPONENT_ID'] : ''
				)
			);
		}
	}

	/**
	 * Unbind specified entity from specified channels.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @param array $bindings Array of channel bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 */
	public static function unbind($entityTypeID, $entityID, array $bindings)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if($entityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		$qty = count($bindings);
		if($qty === 0)
		{
			return;
		}

		$connection = Main\Application::getConnection();
		for($i = 0; $i < $qty; $i++)
		{
			$binding = $bindings[$i];
			if(!is_array($binding))
			{
				continue;
			}

			$typeID = isset($binding['TYPE_ID']) ? (int)$binding['TYPE_ID'] : 0;
			if($typeID <= 0)
			{
				continue;
			}

			$connection->queryExecute(
			/** @lang text */
				"DELETE FROM b_crm_entity_channel WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID} AND TYPE_ID = {$typeID}"
			);
		}
	}
	/**
	 * Unbind specified entity from all channels.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindAll($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if($entityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
			/** @lang text */
			"DELETE FROM b_crm_entity_channel WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID}"
		);
	}
}