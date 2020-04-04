<?php
namespace Bitrix\Crm\Cleaning\Entity;

use Bitrix\Main;

class CleaningTable extends Main\ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_cleaning';
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
			'CREATED_TIME' => array('data_type' => 'datetime', 'required' => true),
			'LAST_UPDATED_TIME' => array('data_type' => 'datetime', 'required' => true)
		);
	}

	public static function upsert(array $data)
	{
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
		$entityID = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;

		$now = new Main\Type\DateTime();

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_cleaning',
			array('ENTITY_TYPE_ID', 'ENTITY_ID'),
			array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
				'CREATED_TIME' => $now,
				'LAST_UPDATED_TIME' => $now
			),
			array('LAST_UPDATED_TIME' => $now)
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
}