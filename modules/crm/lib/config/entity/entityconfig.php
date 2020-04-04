<?php
namespace Bitrix\Crm\Config\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Crm;

class EntityConfigTable extends Entity\DataManager
{
	/**
	 * Get entity table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_entity_cfg';
	}
	/**
	 * Get entity fields map.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ENTITY_TYPE_ID' => array('data_type' => 'integer', 'required' => true, 'primary' => true),
			'ENTITY_ID' => array('data_type' => 'integer', 'required' => true, 'primary' => true),
			'USER_ID' => array('data_type' => 'integer', 'required' => true, 'primary' => true),
			'SETTINGS' => array('data_type' => 'text')
		);
	}
	/**
	 * Execute UPSERT operation.
	 * @param array $data Field data.
	 * @return void
	 */
	public static function upsert(array $data)
	{
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : 0;
		if($entityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must contains ENTITY_TYPE_ID field.', 'data');
		}

		$entityID = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must contains ENTITY_ID field.', 'data');
		}

		$userID = isset($data['USER_ID']) ? (int)$data['USER_ID'] : 0;
		if($userID <= 0)
		{
			throw new Main\ArgumentException('Must contains USER_ID field.', 'data');
		}

		$settings = isset($data['SETTINGS']) ? $data['SETTINGS'] : '';

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_entity_cfg',
			array('ENTITY_TYPE_ID', 'ENTITY_ID', 'USER_ID'),
			array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID, 'USER_ID' => $userID, 'SETTINGS' => $settings),
			array('SETTINGS' => $settings)
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
	/**
	 * Delete by entity.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function deleteByEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
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
		$helper = $connection->getSqlHelper();
		$conditionSql = implode(
			' AND ',
			array(
				$helper->prepareAssignment('b_crm_entity_cfg', 'ENTITY_TYPE_ID', $entityTypeID),
				$helper->prepareAssignment('b_crm_entity_cfg', 'ENTITY_ID', $entityID)
			)
		);
		$connection->queryExecute('DELETE FROM b_crm_entity_cfg WHERE '.$conditionSql);
	}
}