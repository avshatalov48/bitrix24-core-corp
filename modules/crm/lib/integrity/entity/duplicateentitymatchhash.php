<?php
namespace Bitrix\Crm\Integrity\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DuplicateEntityMatchHashTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_entity_hash';
	}
	public static function getMap()
	{
		return array(
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'required' => true
			),
			'ENTITY_TYPE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'required' => true
			),
			'TYPE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'required' => true
			),
			'MATCH_HASH' => array(
				'data_type' => 'string',
				'primary' => true,
				'required' => true
			),
			'SCOPE' => array(
				'data_type' => 'string',
				'primary' => true,
				'required' => true
			),
			'IS_PRIMARY' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			)
		);
	}
	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$entityID = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : 0;
		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : 0;
		$matchHash = isset($data['MATCH_HASH']) ? $sqlHelper->forSql($data['MATCH_HASH'], 32) : '';

		$isPrimary = 'N';
		if(isset($data['IS_PRIMARY']))
		{
			if(is_bool($data['IS_PRIMARY']))
			{
				$isPrimary = $data['IS_PRIMARY'] ? 'Y' : 'N';
			}
			elseif(is_string($data['IS_PRIMARY']) && strtoupper(trim($data['IS_PRIMARY'])) === 'Y')
			{
				$isPrimary = 'Y';
			}
		}

		$scope = isset($data['SCOPE']) ? $sqlHelper->forSql($data['SCOPE'], 6) : '';

		if($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$connection->queryExecute(
				"INSERT INTO b_crm_dp_entity_hash(ENTITY_ID, ENTITY_TYPE_ID, TYPE_ID, MATCH_HASH, SCOPE, IS_PRIMARY)
					VALUES({$entityID}, {$entityTypeID}, {$typeID}, '{$matchHash}', '{$scope}', '{$isPrimary}')
					ON DUPLICATE KEY UPDATE IS_PRIMARY = '{$isPrimary}'"
			);
		}
		elseif($connection instanceof Main\DB\MssqlConnection)
		{
			$dbResult = $connection->query(
				"SELECT 'X' FROM b_crm_dp_entity_hash WHERE ENTITY_ID = {$entityID} AND ENTITY_TYPE_ID = {$entityTypeID} AND TYPE_ID = {$typeID} AND MATCH_HASH = '{$matchHash}' AND SCOPE = '{$scope}'"
			);

			if(!is_array($dbResult->fetch()))
			{
				$connection->queryExecute(
					"INSERT INTO b_crm_dp_entity_hash(ENTITY_ID, ENTITY_TYPE_ID, TYPE_ID, MATCH_HASH, SCOPE, IS_PRIMARY)
						VALUES({$entityID}, {$entityTypeID}, {$typeID}, '{$matchHash}', '{$scope}', '{$isPrimary}')"
				);
			}
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$connection->queryExecute("MERGE INTO b_crm_dp_entity_hash USING (SELECT {$entityID} ENTITY_ID, {$entityTypeID} ENTITY_TYPE_ID, {$typeID} TYPE_ID, '{$matchHash}' MATCH_HASH, '{$scope}' SCOPE FROM dual)
				source ON
				(
					source.ENTITY_ID = b_crm_dp_entity_hash.ENTITY_ID
					AND source.ENTITY_TYPE_ID = b_crm_dp_entity_hash.ENTITY_TYPE_ID
					AND source.TYPE_ID = b_crm_dp_entity_hash.TYPE_ID
					AND source.MATCH_HASH = b_crm_dp_entity_hash.MATCH_HASH
					AND source.SCOPE = b_crm_dp_entity_hash.SCOPE
				)
				WHEN MATCHED THEN
					UPDATE SET b_crm_dp_entity_hash.IS_PRIMARY = '{$isPrimary}'
				WHEN NOT MATCHED THEN
					INSERT (ENTITY_ID, ENTITY_TYPE_ID, TYPE_ID, MATCH_HASH, SCOPE, IS_PRIMARY)
					VALUES({$entityID}, {$entityTypeID}, {$typeID}, '{$matchHash}', '{$scope}', '{$isPrimary}')"
			);
		}
		else
		{
			$dbType = $connection->getType();
			throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
		}
	}
	public static function deleteByFilter(array $filter)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$conditions = array();

		$entityID = isset($filter['ENTITY_ID']) ? (int)$filter['ENTITY_ID'] : 0;
		if($entityID > 0)
		{
			$conditions[] = "ENTITY_ID = {$entityID}";
		}

		$entityTypeID = isset($filter['ENTITY_TYPE_ID']) ? (int)$filter['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
		if($entityTypeID > 0)
		{
			$conditions[] = "ENTITY_TYPE_ID = {$entityTypeID}";
		}

		if(isset($filter['TYPE_ID']))
		{
			if(is_array($filter['TYPE_ID']))
			{
				if(!empty($filter['TYPE_ID']))
				{
					$typeIds = '';
					$i = 0;
					foreach ($filter['TYPE_ID'] as $value)
					{
						if($value > 0)
							$typeIds .= ($i++ === 0 ? '' : ',').(int)$value;
					}
					if(!empty($typeIds))
					{
						$typeIds = 'TYPE_ID IN ('.$typeIds.')';
						$conditions[] = $typeIds;
					}
					unset($typeIds);
				}
			}
			else
			{
				$typeID = (int)$filter['TYPE_ID'];
				if($typeID > 0)
				{
					$conditions[] = "TYPE_ID = {$typeID}";
				}
			}
		}

		if(isset($filter['SCOPE']))
		{
			if (is_array($filter['SCOPE']))
			{
				if (!empty($filter['SCOPE']))
				{
					$scopes = '';
					$i = 0;
					foreach ($filter['SCOPE'] as $value)
					{
						$value = strval($value);
						if(!empty($value))
							$scopes .= ($i++ === 0 ? '' : ',')."'".$sqlHelper->forSql($value)."'";
					}
					if(!empty($scopes))
					{
						$scopes = 'SCOPE IN ('.$scopes.')';
						$conditions[] = $scopes;
					}
					unset($scopes);
				}
			}
			else
			{
				$scope = $sqlHelper->forSql($filter['SCOPE'], 6);
				$conditions[] = "SCOPE = '{$scope}'";
			}
		}

		if(!empty($conditions))
		{
			Main\Application::getConnection()->queryExecute('DELETE FROM  b_crm_dp_entity_hash WHERE '.implode(' AND ', $conditions));
		}
	}
}