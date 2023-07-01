<?php
namespace Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Entity;

/**
 * Class AddressTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Address_Query query()
 * @method static EO_Address_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Address_Result getById($id)
 * @method static EO_Address_Result getList(array $parameters = [])
 * @method static EO_Address_Entity getEntity()
 * @method static \Bitrix\Crm\EO_Address createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Address_Collection createCollection()
 * @method static \Bitrix\Crm\EO_Address wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Address_Collection wakeUpCollection($rows)
 */
class AddressTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_addr';
	}
	public static function getMap()
	{
		return [
			'TYPE_ID' => ['data_type' => 'integer', 'primary' => true, 'required' => true],
			'ENTITY_TYPE_ID' => ['data_type' => 'integer', 'primary' => true, 'required' => true],
			'ENTITY_ID' => ['data_type' => 'integer', 'primary' => true, 'required' => true],
			'ANCHOR_TYPE_ID' => ['data_type' => 'integer'],
			'ANCHOR_ID' => ['data_type' => 'integer'],
			'ADDRESS_1' => ['data_type' => 'string'],
			'ADDRESS_2' => ['data_type' => 'string'],
			'CITY' => ['data_type' => 'string'],
			'POSTAL_CODE' => ['data_type' => 'string'],
			'REGION' => ['data_type' => 'string'],
			'PROVINCE' => ['data_type' => 'string'],
			'COUNTRY' => ['data_type' => 'string'],
			'COUNTRY_CODE' => ['data_type' => 'string'],
			'LOC_ADDR_ID' => ['data_type' => 'integer', 'default_value' => 0],
			'IS_DEF' => ['data_type' => 'boolean', 'values' => [0, 1], 'default_value' => 0]
		];
	}
	public static function upsert(array $data)
	{
		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : 0;
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : 0;
		$entityID = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;

		$fields = [
			'ANCHOR_TYPE_ID' => isset($data['ANCHOR_TYPE_ID']) ? (int)$data['ANCHOR_TYPE_ID'] : $entityTypeID,
			'ANCHOR_ID' => isset($data['ANCHOR_ID']) ? (int)$data['ANCHOR_ID'] : $entityID,
			'ADDRESS_1' => isset($data['ADDRESS_1']) && $data['ADDRESS_1'] !== '' ? $data['ADDRESS_1'] : null,
			'ADDRESS_2' => isset($data['ADDRESS_2']) && $data['ADDRESS_2'] !== '' ? $data['ADDRESS_2'] : null,
			'CITY' => isset($data['CITY']) && $data['CITY'] !== '' ? $data['CITY'] : null,
			'POSTAL_CODE' => isset($data['POSTAL_CODE']) && $data['POSTAL_CODE'] !== '' ? $data['POSTAL_CODE'] : null,
			'REGION' => isset($data['REGION']) && $data['REGION'] !== '' ? $data['REGION'] : null,
			'PROVINCE' => isset($data['PROVINCE']) && $data['PROVINCE'] !== '' ? $data['PROVINCE'] : null,
			'COUNTRY' => isset($data['COUNTRY']) && $data['COUNTRY'] !== '' ? $data['COUNTRY'] : null,
			'COUNTRY_CODE' => isset($data['COUNTRY_CODE']) && $data['COUNTRY_CODE'] !== '' ?
				$data['COUNTRY_CODE'] : null,
			'LOC_ADDR_ID' => isset($data['LOC_ADDR_ID']) ? (int)$data['LOC_ADDR_ID'] : 0,
			'IS_DEF' => (isset($data['IS_DEF']) && $data['IS_DEF'] === true) ? 1 : 0
		];

		$connection = Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_addr',
			['TYPE_ID', 'ENTITY_TYPE_ID', 'ENTITY_ID'],
			array_merge(
				$fields,
				['TYPE_ID' => $typeID, 'ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID]
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

		$connection = Application::getConnection();
		//HACK: DEFINE TYPE_ID IN WHERE CONDITION FOR MAKE MYSQL USE PK IN EFFECTIVE WAY
		$typeSlug = implode(',', EntityAddressType::getAllIDs());
		$connection->queryExecute(
			"UPDATE b_crm_addr SET ENTITY_TYPE_ID = {$newEntityTypeID}, ENTITY_ID = {$newEntityID} ".
			"WHERE TYPE_ID IN({$typeSlug}) AND ENTITY_TYPE_ID = {$oldEntityTypeID} AND ENTITY_ID = {$oldEntityID}"
		);
		$connection->queryExecute(
			"UPDATE b_crm_addr SET ANCHOR_TYPE_ID = {$newEntityTypeID}, ANCHOR_ID = {$newEntityID} ".
			"WHERE TYPE_ID IN({$typeSlug}) AND ANCHOR_TYPE_ID = {$oldEntityTypeID} AND ANCHOR_ID = {$oldEntityID}"
		);
	}
	public static function dropLocationAddressLink($locationAddressId)
	{
		$locationAddressId = (int)$locationAddressId;
		if($locationAddressId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'locationAddressId');
		}

		$connection = Application::getConnection();
		$connection->queryExecute(
			"UPDATE b_crm_addr SET LOC_ADDR_ID = 0 ".
			"WHERE LOC_ADDR_ID = {$locationAddressId}"
		);
	}
	public static function setDef(array $data)
	{
		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : 0;
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : 0;
		$entityID = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;

		if ($entityTypeID > 0 && $entityID > 0)
		{
			Application::getConnection()->queryExecute(
			/** @lang MySQL */
				"UPDATE b_crm_addr A ".PHP_EOL.
				"  INNER JOIN (".PHP_EOL.
				"    SELECT R2.ID FROM b_crm_requisite R2 ".PHP_EOL.
				"      INNER JOIN (".PHP_EOL.
				"        SELECT R1.ENTITY_TYPE_ID, R1.ENTITY_ID ".PHP_EOL.
				"        FROM b_crm_requisite R1 ".PHP_EOL.
				"        WHERE R1.ID = {$entityID}".PHP_EOL.
				"      ) AN ON R2.ENTITY_TYPE_ID = AN.ENTITY_TYPE_ID AND R2.ENTITY_ID = AN.ENTITY_ID ".PHP_EOL.
				"	) R ON A.ENTITY_TYPE_ID = {$entityTypeID} AND A.ENTITY_ID = R.ID ".PHP_EOL.
				"SET A.IS_DEF = IF(A.ENTITY_ID = {$entityID}, 1, 0) ".PHP_EOL.
				"WHERE ".($typeID > 0 ? "A.TYPE_ID = {$typeID}" : "(1=1)")
			);
		}
	}
}