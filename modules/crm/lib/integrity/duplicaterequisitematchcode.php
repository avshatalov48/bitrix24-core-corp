<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DuplicateRequisiteMatchCodeTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_rq_mcd';
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
			'RQ_COUNTRY_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'RQ_FIELD_NAME' => array(
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
		$connection = Application::getConnection();
		if(empty($data))
		{
			$connection->queryExecute(
				/** @lang MySQL */
				"DELETE FROM b_crm_dp_rq_mcd WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID}"
			);
			return;
		}

		$currentMap = array();
		foreach($data as $rqCountryId => $rqFields)
		{
			if(is_array($rqFields))
			{
				if (!isset($currentMap[$rqCountryId]))
				{
					$currentMap[$rqCountryId] = array();
				}

				foreach ($rqFields as $rqFieldName => $values)
				{
					if (is_array($values))
					{
						if (!isset($currentMap[$rqCountryId][$rqFieldName]))
						{
							$currentMap[$rqCountryId][$rqFieldName] = array();
						}

						foreach($values as $value)
						{
							$hash = md5($value);
							$currentMap[$rqCountryId][$rqFieldName][$hash] = array('value' => $value);
						}
					}
				}
			}
		}

		$persistentMap = array();
		$result = $connection->query(
			/** @lang MySQL */
			"SELECT ID, RQ_COUNTRY_ID, RQ_FIELD_NAME, VALUE".PHP_EOL.
			"FROM b_crm_dp_rq_mcd".PHP_EOL.
			"\tWHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID}"
		);
		while($fields = $result->fetch())
		{
			$rqCountryId = $fields['RQ_COUNTRY_ID'];
			$rqFieldName = $fields['RQ_FIELD_NAME'];
			if(!isset($persistentMap[$rqCountryId]))
			{
				$persistentMap[$rqCountryId] = array();
			}
			if(!isset($persistentMap[$rqCountryId][$rqFieldName]))
			{
				$persistentMap[$rqCountryId][$rqFieldName] = array();
			}

			$ID = (int)$fields['ID'];
			$value = $fields['VALUE'];
			$hash = md5($value);
			$persistentMap[$rqCountryId][$rqFieldName][$hash] = array('id' => $ID, 'value' => $value);
		}

		$deleteIDs = array();
		foreach($persistentMap as $rqCountryId => $rqFields)
		{
			foreach ($rqFields as $rqFieldName => $items)
			{
				$currentItems = isset($currentMap[$rqCountryId][$rqFieldName]) ?
					$currentMap[$rqCountryId][$rqFieldName] : array();
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
		foreach($currentMap as $rqCountryId => $rqFields)
		{
			foreach ($rqFields as $rqFieldName => $items)
			{
				$presentItems = isset($persistentMap[$rqCountryId][$rqFieldName]) ?
					$persistentMap[$rqCountryId][$rqFieldName] : array();
				foreach($items as $hash => $item)
				{
					if(!isset($presentItems[$hash]))
					{
						$insertItems[] = array(
							'rqCountryId' => $rqCountryId,
							'rqFieldName' => $rqFieldName,
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
				"DELETE FROM b_crm_dp_rq_mcd WHERE ID IN ({$idsSql})"
			);
		}

		if(!empty($insertItems))
		{
			$valueData = array();
			foreach($insertItems as $item)
			{
				$rqCountryIdSql = (int)$item['rqCountryId'];
				$rqFieldNameSql = $sqlHelper->forSql($item['rqFieldName']);
				$valueSql = $sqlHelper->forSql($item['value']);
				$valueData[] =
					"({$entityTypeID}, {$entityID}, {$rqCountryIdSql}, '{$rqFieldNameSql}', '{$valueSql}')";
			}

			$valuesSql = implode(', ', $valueData);
			$connection->queryExecute(
				/** @lang MySQL */
				"INSERT INTO b_crm_dp_rq_mcd".PHP_EOL.
				"\t(ENTITY_TYPE_ID, ENTITY_ID, RQ_COUNTRY_ID, RQ_FIELD_NAME, VALUE) VALUES ".PHP_EOL.
				"\t{$valuesSql}"
			);
		}
	}
	public static function replaceValues($entityTypeID, $entityID, $requsiteCountryId, $requisiteFieldName, array $values)
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$sqlHelper = $connection->getSqlHelper();
		$rqCountryIdSql = (int)$requsiteCountryId;
		$rqFieldNameSql = $sqlHelper->forSql($requisiteFieldName);

		if(empty($values))
		{
			$connection->queryExecute(
				/** @lang MySQL */
				"DELETE FROM b_crm_dp_rq_mcd".PHP_EOL.
				"\tWHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID}".PHP_EOL.
				"\tAND RQ_FIELD_NAME = '{$rqFieldNameSql}' AND RQ_COUNTRY_ID = {$rqCountryIdSql}"
			);
			return;
		}

		$items = array();
		$result = $connection->query(
			/** @lang MySQL */
			"SELECT ID, VALUE".PHP_EOL.
			"FROM b_crm_dp_rq_mcd".PHP_EOL.
			"\tWHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID}".PHP_EOL.
			"\tAND RQ_FIELD_NAME = '{$rqFieldNameSql}' AND RQ_COUNTRY_ID = {$rqCountryIdSql}"
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
				"DELETE FROM b_crm_dp_rq_mcd WHERE ID IN ({$idsSql})"
			);
		}

		if(!empty($insertValues))
		{
			$valueData = array();
			foreach($insertValues as $value)
			{
				$valueSql = $sqlHelper->forSql($value);
				$valueData[] = "({$entityTypeID}, {$entityID}, {$rqCountryIdSql}, '{$rqFieldNameSql}', '{$valueSql}')";
			}

			$valuesSql = implode(', ', $valueData);
			$connection->queryExecute(
				/** @lang MySQL */
				"INSERT INTO b_crm_dp_rq_mcd".PHP_EOL.
				"\t(ENTITY_TYPE_ID, ENTITY_ID, RQ_COUNTRY_ID, RQ_FIELD_NAME, VALUE)".PHP_EOL
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

		$sql = 'SELECT DISTINCT RQ_COUNTRY_ID, RQ_FIELD_NAME FROM b_crm_dp_rq_mcd';
		if ($entityTypeID !== \CCrmOwnerType::Undefined)
			$sql .= ' WHERE ENTITY_TYPE_ID = '.$entityTypeID;
		$connection = Application::getConnection();
		$res = $connection->query($sql);
		$results = array();
		while($row = $res->fetch())
		{
			$results[$row['RQ_COUNTRY_ID']][] = $row['RQ_FIELD_NAME'];
		}
		return $results;
	}
}