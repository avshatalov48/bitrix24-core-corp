<?php
namespace Bitrix\Crm\Observer\Entity;

use Bitrix\Main;

class ObserverTable extends Main\ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_observer';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ENTITY_TYPE_ID' => array('data_type' => 'integer', 'primary' => true),
			'ENTITY_ID' => array('data_type' => 'integer', 'primary' => true),
			'USER_ID' => array('data_type' => 'integer', 'primary' => true),
			'SORT' => array('data_type' => 'integer', 'default_value' => 0),
			'CREATED_TIME' => array('data_type' => 'datetime', 'required' => true),
			'LAST_UPDATED_TIME' => array('data_type' => 'datetime', 'required' => true)
		);
	}

	public static function upsert(array $data)
	{
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
		$entityID = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;
		$userID = isset($data['USER_ID']) ? (int)$data['USER_ID'] : 0;
		$sort = isset($data['SORT']) ? (int)$data['SORT'] : 0;

		$now = new Main\Type\DateTime();

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_observer',
			array('ENTITY_TYPE_ID', 'ENTITY_ID', 'USER_ID'),
			array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
				'USER_ID' => $userID,
				'SORT' => $sort,
				'CREATED_TIME' => $now,
				'LAST_UPDATED_TIME' => $now
			),
			array('SORT' => $sort, 'LAST_UPDATED_TIME' => $now)
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}

	public static function deleteByFilter(array $filter)
	{
		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$tableName = self::getTableName();

		$conditions = array();
		foreach($filter as $k => $v)
		{
			$conditions[] = $helper->prepareAssignment($tableName, $k, $v);
		}
		$connection->queryExecute('DELETE FROM '.$tableName.' WHERE '.implode(' AND ', $conditions));
	}

	/**
	 * Unbind observers from old entity of one type and bind them to new entity of another type.
	 * @param integer $oldEntityTypeID Old Entity Type ID.
	 * @param integer $oldEntityID Old Old Entity ID.
	 * @param integer $newEntityTypeID New Entity Type ID.
	 * @param integer $newEntityID New Entity ID.
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectException
	 */
	public static function transferOwnership($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		if(!is_int($oldEntityTypeID))
		{
			$oldEntityTypeID = (int)$oldEntityTypeID;
		}

		if($oldEntityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityTypeID');
		}

		if(!is_int($oldEntityID))
		{
			$oldEntityID = (int)$oldEntityID;
		}

		if($oldEntityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityID');
		}

		if(!is_int($newEntityTypeID))
		{
			$newEntityTypeID = (int)$newEntityTypeID;
		}

		if($newEntityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityTypeID');
		}

		if(!is_int($newEntityID))
		{
			$newEntityID = (int)$newEntityID;
		}

		if($newEntityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityID');
		}

		$data = array();
		$now = new Main\Type\DateTime();
		$connection = Main\Application::getConnection();
		$dbResult = $connection->query(
			"SELECT USER_ID, SORT FROM b_crm_observer WHERE ENTITY_TYPE_ID = {$oldEntityTypeID} AND ENTITY_ID = {$oldEntityID}"
		);

		while($fields = $dbResult->fetch())
		{
			$data[] = $fields;
		}

		if(empty($data))
		{
			return;
		}

		foreach($data as $item)
		{
			$queries = $connection->getSqlHelper()->prepareMerge(
				'b_crm_observer',
				array('ENTITY_TYPE_ID', 'ENTITY_ID', 'USER_ID'),
				array(
					'ENTITY_TYPE_ID' => $newEntityTypeID,
					'ENTITY_ID' => $newEntityID,
					'USER_ID' => $item['USER_ID'],
					'SORT' => $item['SORT'],
					'CREATED_TIME' => $now,
					'LAST_UPDATED_TIME' => $now
				),
				array('SORT' => $item['SORT'], 'LAST_UPDATED_TIME' => $now)
			);

			foreach($queries as $query)
			{
				$connection->queryExecute($query);
			}
		}

		$connection->queryExecute(
			"DELETE FROM b_crm_observer WHERE ENTITY_TYPE_ID = {$oldEntityTypeID} AND ENTITY_ID = {$oldEntityID}"
		);
	}
}