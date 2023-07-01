<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class DuplicateVolatileMatchCodeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DuplicateVolatileMatchCode_Query query()
 * @method static EO_DuplicateVolatileMatchCode_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DuplicateVolatileMatchCode_Result getById($id)
 * @method static EO_DuplicateVolatileMatchCode_Result getList(array $parameters = [])
 * @method static EO_DuplicateVolatileMatchCode_Entity getEntity()
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateVolatileMatchCode createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateVolatileMatchCode_Collection createCollection()
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateVolatileMatchCode wakeUpObject($row)
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateVolatileMatchCode_Collection wakeUpCollection($rows)
 */
class DuplicateVolatileMatchCodeTable extends Entity\DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_dp_vol_mcd';
	}

	public static function getMap(): array
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			],
			'ENTITY_ID' => [
				'data_type' => 'integer',
				'required' => true
			],
			'ENTITY_TYPE_ID' => [
				'data_type' => 'integer',
				'required' => true
			],
			'TYPE_ID' => [
				'data_type' => 'integer',
				'required' => true
			],
			'VALUE' => [
				'data_type' => 'string',
				'required' => true
			]
		];
	}

	public static function replaceValues(int $entityTypeId, int $entityId, int $typeId, array $values)
	{
		$connection = Application::getConnection();

		$sqlHelper = $connection->getSqlHelper();

		if(empty($values))
		{
			$tableName = static::getTableName();
			$connection->queryExecute(
				"DELETE FROM $tableName "
				. "WHERE ENTITY_TYPE_ID = $entityTypeId AND ENTITY_ID = $entityId AND TYPE_ID = $typeId"
			);

			return;
		}

		$items = [];
		$tableName = static::getTableName();
		$result = $connection->query(
			"SELECT ID, VALUE "
			. "FROM $tableName "
			. "WHERE ENTITY_TYPE_ID = $entityTypeId AND ENTITY_ID = $entityId AND TYPE_ID = $typeId"
		);
		while($fields = $result->fetch())
		{
			$items[intval($fields['ID'])] = $fields['VALUE'];
		}

		$deleteIds = [];
		foreach($items as $itemId => $itemValue)
		{
			if(!in_array($itemValue, $values, true))
			{
				$deleteIds[] = $itemId;
			}
		}

		$insertValues = [];
		foreach($values as $value)
		{
			if(!is_string($value) || $value === '')
			{
				continue;
			}

			if(!in_array($value, $items, true))
			{
				$insertValues[] = $value;
			}
		}

		if(!empty($deleteIds))
		{
			$idsSql = implode(',', $deleteIds);
			$tableName = static::getTableName();
			$connection->queryExecute("DELETE FROM $tableName WHERE ID IN ($idsSql)");
		}

		if(!empty($insertValues))
		{
			$valueData = [];
			foreach($insertValues as $value)
			{
				$valueSql = $sqlHelper->forSql($value);
				$valueData[] = "($entityTypeId, $entityId, $typeId, '$valueSql')";
			}

			$valuesSql = implode(', ', $valueData);
			$tableName = static::getTableName();
			$connection->queryExecute(
				"INSERT INTO $tableName(ENTITY_TYPE_ID, ENTITY_ID, TYPE_ID, VALUE) VALUES $valuesSql"
			);
		}
	}
}