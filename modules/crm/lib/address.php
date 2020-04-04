<?php
namespace Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\Entity;

class AddressTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_addr';
	}
	public static function getMap()
	{
		return array(
			'TYPE_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'ENTITY_TYPE_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'ENTITY_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'ANCHOR_TYPE_ID' => array('data_type' => 'integer'),
			'ANCHOR_ID' => array('data_type' => 'integer'),
			'ADDRESS_1' => array('data_type' => 'string'),
			'ADDRESS_2' => array('data_type' => 'string'),
			'CITY' => array('data_type' => 'string'),
			'POSTAL_CODE' => array('data_type' => 'string'),
			'REGION' => array('data_type' => 'string'),
			'PROVINCE' => array('data_type' => 'string'),
			'COUNTRY' => array('data_type' => 'string'),
			'COUNTRY_CODE' => array('data_type' => 'string'),
		);
	}
	public static function upsert(array $data)
	{
		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : 0;
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : 0;
		$entityID = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;

		$fields = array(
			'ANCHOR_TYPE_ID' => isset($data['ANCHOR_TYPE_ID']) ? (int)$data['ANCHOR_TYPE_ID'] : $entityTypeID,
			'ANCHOR_ID' => isset($data['ANCHOR_ID']) ? (int)$data['ANCHOR_ID'] : $entityID,
			'ADDRESS_1' => isset($data['ADDRESS_1']) && $data['ADDRESS_1'] !== '' ? $data['ADDRESS_1'] : null,
			'ADDRESS_2' => isset($data['ADDRESS_2']) && $data['ADDRESS_2'] !== '' ? $data['ADDRESS_2'] : null,
			'CITY' => isset($data['CITY']) && $data['CITY'] !== '' ? $data['CITY'] : null,
			'POSTAL_CODE' => isset($data['POSTAL_CODE']) && $data['POSTAL_CODE'] !== '' ? $data['POSTAL_CODE'] : null,
			'REGION' => isset($data['REGION']) && $data['REGION'] !== '' ? $data['REGION'] : null,
			'PROVINCE' => isset($data['PROVINCE']) && $data['PROVINCE'] !== '' ? $data['PROVINCE'] : null,
			'COUNTRY' => isset($data['COUNTRY']) && $data['COUNTRY'] !== '' ? $data['COUNTRY'] : null,
			'COUNTRY_CODE' => isset($data['COUNTRY_CODE']) && $data['COUNTRY_CODE'] !== '' ? $data['COUNTRY_CODE'] : null
		);

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_addr',
			array('TYPE_ID', 'ENTITY_TYPE_ID', 'ENTITY_ID'),
			array_merge(
				$fields,
				array('TYPE_ID' => $typeID, 'ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID)
			),
			$fields
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
	public static function rebind($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		if($oldEntityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityTypeID');
		}

		if($oldEntityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityID');
		}

		if($newEntityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityTypeID');
		}

		if($newEntityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityID');
		}

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
			"UPDATE b_crm_addr SET ENTITY_TYPE_ID = {$newEntityTypeID}, ENTITY_ID = {$newEntityID} WHERE ENTITY_TYPE_ID = {$oldEntityTypeID} AND ENTITY_ID = {$oldEntityID}"
		);
		$connection->queryExecute(
			"UPDATE b_crm_addr SET ANCHOR_TYPE_ID = {$newEntityTypeID}, ANCHOR_ID = {$newEntityID} WHERE ANCHOR_TYPE_ID = {$oldEntityTypeID} AND ANCHOR_ID = {$oldEntityID}"
		);
	}
}