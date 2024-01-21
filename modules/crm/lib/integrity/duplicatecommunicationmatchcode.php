<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class DuplicateCommunicationMatchCodeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DuplicateCommunicationMatchCode_Query query()
 * @method static EO_DuplicateCommunicationMatchCode_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DuplicateCommunicationMatchCode_Result getById($id)
 * @method static EO_DuplicateCommunicationMatchCode_Result getList(array $parameters = [])
 * @method static EO_DuplicateCommunicationMatchCode_Entity getEntity()
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateCommunicationMatchCode createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateCommunicationMatchCode_Collection createCollection()
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateCommunicationMatchCode wakeUpObject($row)
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateCommunicationMatchCode_Collection wakeUpCollection($rows)
 */
class DuplicateCommunicationMatchCodeTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_comm_mcd';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'ENTITY_TYPE_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'TYPE' => array(
				'data_type' => 'string',
				'required' => true
			),
			'VALUE' => array(
				'data_type' => 'string',
				'required' => true
			)
		);
	}

	public static function bulkReplaceValues($entityTypeID, $entityID, array $data)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		if(empty($data))
		{
			$connection->queryExecute(
				"DELETE FROM b_crm_dp_comm_mcd WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID}"
			);
			return;
		}

		$currentMap = array();
		foreach($data as $type => $values)
		{
			if(!is_array($values))
			{
				continue;
			}

			if(!isset($currentMap[$type]))
			{
				$currentMap[$type] = array();
			}

			foreach($values as $value)
			{
				$hash = md5($value);
				$currentMap[$type][$hash] = array('value' => $value);
			}
		}

		$persistentMap = array();
		$result = $connection->query("SELECT ID, TYPE, VALUE FROM b_crm_dp_comm_mcd WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID}");
		while($fields = $result->fetch())
		{
			$type = $fields['TYPE'];
			if(!isset($persistentMap[$type]))
			{
				$persistentMap[$type] = array();
			}

			$ID = (int)$fields['ID'];
			$value = $fields['VALUE'];
			$hash = md5($value);
			$persistentMap[$type][$hash] = array('id' => $ID, 'value' => $value);
		}

		$deleteIDs = array();
		foreach($persistentMap as $type => $items)
		{
			$currentItems = isset($currentMap[$type]) ? $currentMap[$type] : array();
			foreach($items as $hash => $item)
			{
				if(!isset($currentItems[$hash]))
				{
					$deleteIDs[] = $item['id'];
				}
			}
		}

		$insertItems = array();
		foreach($currentMap as $type => $items)
		{
			$presentItems = isset($persistentMap[$type]) ? $persistentMap[$type] : array();
			foreach($items as $hash => $item)
			{
				if(!isset($presentItems[$hash]))
				{
					$insertItems[] = array('type' => $type, 'value' =>$item['value']);
				}
			}
		}

		$sqlHelper = $connection->getSqlHelper();
		if(!empty($deleteIDs))
		{
			$idsSql = implode(',', $deleteIDs);
			$connection->queryExecute(
				"DELETE FROM b_crm_dp_comm_mcd WHERE ID IN ({$idsSql})"
			);
		}

		if(!empty($insertItems))
		{
			$valueData = [];
			foreach($insertItems as $item)
			{
				$typeSql = $sqlHelper->forSql($item['type']);
				$valueSql = $sqlHelper->forSql($item['value']);
				$valueData[] = "({$entityTypeID}, {$entityID}, '{$typeSql}', '{$valueSql}')";
			}

			$valuesSql = implode(', ', $valueData);
			$connection->queryExecute(
				"INSERT INTO b_crm_dp_comm_mcd(ENTITY_TYPE_ID, ENTITY_ID, TYPE, VALUE) VALUES {$valuesSql}"
			);
		}
	}
	public static function replaceValues($entityTypeID, $entityID, $type, array $values)
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$sqlHelper = $connection->getSqlHelper();
		$typeSql = $sqlHelper->forSql($type);

		if(empty($values))
		{
			$connection->queryExecute(
				"DELETE FROM b_crm_dp_comm_mcd WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID} AND TYPE = '{$typeSql}'"
			);
			return;
		}

		$items = array();
		$result = $connection->query("SELECT ID, VALUE FROM b_crm_dp_comm_mcd WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID} AND TYPE = '{$typeSql}'");
		while($fields = $result->fetch())
		{
			$items[intval($fields['ID'])] = $fields['VALUE'];
		}

		$deleteIDs = array();
		foreach($items as $itemID => $itemValue)
		{
			if(!in_array($itemValue, $values, true))
			{
				$deleteIDs[] = $itemID;
			}
		}

		$insertValues = array();
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

		if(!empty($deleteIDs))
		{
			$idsSql = implode(',', $deleteIDs);
			$connection->queryExecute(
				"DELETE FROM b_crm_dp_comm_mcd WHERE ID IN ({$idsSql})"
			);
		}

		if(!empty($insertValues))
		{
			$valueData = [];
			foreach($insertValues as $value)
			{
				$valueSql = $sqlHelper->forSql($value);
				$valueData[] = "({$entityTypeID}, {$entityID}, '{$typeSql}', '{$valueSql}')";
			}

			$valuesSql = implode(', ', $valueData);
			$connection->queryExecute(
				"INSERT INTO b_crm_dp_comm_mcd(ENTITY_TYPE_ID, ENTITY_ID, TYPE, VALUE) VALUES {$valuesSql}"
			);
		}
	}
}