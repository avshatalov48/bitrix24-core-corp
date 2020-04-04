<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DuplicateBankDetailMatchCodeTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_bd_mcd';
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
			'BD_COUNTRY_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'BD_FIELD_NAME' => array(
				'data_type' => 'string',
				'required' => true
			),
			'VALUE' => array(
				'data_type' => 'string',
				'required' => true
			)
		);
	}

	/**
	 * @param $entityTypeID
	 * @param $entityID
	 * @param array $data
	 */
	public static function bulkReplaceValues($entityTypeID, $entityID, array $data)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		if(empty($data))
		{
			$connection->queryExecute(
				/** @lang MySQL */
				"DELETE FROM b_crm_dp_bd_mcd WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID}"
			);
			return;
		}

		$currentMap = array();
		foreach($data as $bdCountryId => $rqFields)
		{
			if(is_array($rqFields))
			{
				if (!isset($currentMap[$bdCountryId]))
				{
					$currentMap[$bdCountryId] = array();
				}

				foreach ($rqFields as $bdFieldName => $values)
				{
					if (is_array($values))
					{
						if (!isset($currentMap[$bdCountryId][$bdFieldName]))
						{
							$currentMap[$bdCountryId][$bdFieldName] = array();
						}

						foreach($values as $value)
						{
							$hash = md5($value);
							$currentMap[$bdCountryId][$bdFieldName][$hash] = array('value' => $value);
						}
					}
				}
			}
		}

		$persistentMap = array();
		$result = $connection->query(
			/** @lang MySQL */
			"SELECT ID, BD_COUNTRY_ID, BD_FIELD_NAME, VALUE".PHP_EOL.
			"FROM b_crm_dp_bd_mcd".PHP_EOL.
			"\tWHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID}"
		);
		while($fields = $result->fetch())
		{
			$bdCountryId = $fields['BD_COUNTRY_ID'];
			$bdFieldName = $fields['BD_FIELD_NAME'];
			if(!isset($persistentMap[$bdCountryId]))
			{
				$persistentMap[$bdCountryId] = array();
			}
			if(!isset($persistentMap[$bdCountryId][$bdFieldName]))
			{
				$persistentMap[$bdCountryId][$bdFieldName] = array();
			}

			$ID = (int)$fields['ID'];
			$value = $fields['VALUE'];
			$hash = md5($value);
			$persistentMap[$bdCountryId][$bdFieldName][$hash] = array('id' => $ID, 'value' => $value);
		}

		$deleteIDs = array();
		foreach($persistentMap as $bdCountryId => $rqFields)
		{
			foreach ($rqFields as $bdFieldName => $items)
			{
				$currentItems = isset($currentMap[$bdCountryId][$bdFieldName]) ?
					$currentMap[$bdCountryId][$bdFieldName] : array();
				foreach($items as $hash => $item)
				{
					if(!isset($currentItems[$hash]))
					{
						$deleteIDs[] = $item['id'];
					}
				}
			}
		}

		$insertItems = array();
		foreach($currentMap as $bdCountryId => $rqFields)
		{
			foreach ($rqFields as $bdFieldName => $items)
			{
				$presentItems = isset($persistentMap[$bdCountryId][$bdFieldName]) ?
					$persistentMap[$bdCountryId][$bdFieldName] : array();
				foreach($items as $hash => $item)
				{
					if(!isset($presentItems[$hash]))
					{
						$insertItems[] = array(
							'bdCountryId' => $bdCountryId,
							'bdFieldName' => $bdFieldName,
							'value' => $item['value']
						);
					}
				}
			}
		}

		$sqlHelper = $connection->getSqlHelper();
		if(!empty($deleteIDs))
		{
			$idsSql = implode(',', $deleteIDs);
			$connection->queryExecute(
				/** @lang MySQL */
				"DELETE FROM b_crm_dp_bd_mcd WHERE ID IN ({$idsSql})"
			);
		}

		if(!empty($insertItems))
		{
			$valueData = array();
			foreach($insertItems as $item)
			{
				$bdCountryIdSql = (int)$item['bdCountryId'];
				$bdFieldNameSql = $sqlHelper->forSql($item['bdFieldName']);
				$valueSql = $sqlHelper->forSql($item['value']);
				$valueData[] =
					"({$entityTypeID}, {$entityID}, {$bdCountryIdSql}, '{$bdFieldNameSql}', '{$valueSql}')";
			}

			$valuesSql = implode(', ', $valueData);
			$connection->queryExecute(
				/** @lang MySQL */
				"INSERT INTO b_crm_dp_bd_mcd".PHP_EOL.
				"\t(ENTITY_TYPE_ID, ENTITY_ID, BD_COUNTRY_ID, BD_FIELD_NAME, VALUE) VALUES ".PHP_EOL.
				"\t{$valuesSql}"
			);
		}
	}
	public static function replaceValues($entityTypeID, $entityID, $requsiteCountryId, $requisiteFieldName, array $values)
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$sqlHelper = $connection->getSqlHelper();
		$bdCountryIdSql = (int)$requsiteCountryId;
		$bdFieldNameSql = $sqlHelper->forSql($requisiteFieldName);

		if(empty($values))
		{
			$connection->queryExecute(
				/** @lang MySQL */
				"DELETE FROM b_crm_dp_bd_mcd".PHP_EOL.
				"\tWHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID}".PHP_EOL.
				"\tAND BD_FIELD_NAME = '{$bdFieldNameSql}' AND BD_COUNTRY_ID = {$bdCountryIdSql}"
			);
			return;
		}

		$items = array();
		$result = $connection->query(
			/** @lang MySQL */
			"SELECT ID, VALUE".PHP_EOL.
			"FROM b_crm_dp_bd_mcd".PHP_EOL.
			"\tWHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID}".PHP_EOL.
			"\tAND BD_FIELD_NAME = '{$bdFieldNameSql}' AND BD_COUNTRY_ID = {$bdCountryIdSql}"
		);
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
				/** @lang MySQL */
				"DELETE FROM b_crm_dp_bd_mcd WHERE ID IN ({$idsSql})"
			);
		}

		if(!empty($insertValues))
		{
			$valueData = array();
			foreach($insertValues as $value)
			{
				$valueSql = $sqlHelper->forSql($value);
				$valueData[] = "({$entityTypeID}, {$entityID}, {$bdCountryIdSql}, '{$bdFieldNameSql}', '{$valueSql}')";
			}

			$valuesSql = implode(', ', $valueData);
			$connection->queryExecute(
				/** @lang MySQL */
				"INSERT INTO b_crm_dp_bd_mcd".PHP_EOL.
				"\t(ENTITY_TYPE_ID, ENTITY_ID, BD_COUNTRY_ID, BD_FIELD_NAME, VALUE)".PHP_EOL
				."\tVALUES {$valuesSql}"
			);
		}
	}
	public static function getIndexedFieldsMap($entityTypeID = \CCrmOwnerType::Undefined)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		$sql = 'SELECT DISTINCT BD_COUNTRY_ID, BD_FIELD_NAME FROM b_crm_dp_bd_mcd';
		if ($entityTypeID !== \CCrmOwnerType::Undefined)
			$sql .= ' WHERE ENTITY_TYPE_ID = '.$entityTypeID;
		$connection = Application::getConnection();
		$res = $connection->query($sql);
		$results = array();
		while($row = $res->fetch())
		{
			$results[$row['BD_COUNTRY_ID']][] = $row['BD_FIELD_NAME'];
		}
		return $results;
	}
}