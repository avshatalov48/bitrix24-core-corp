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
			),
			'DATE_MODIFY' => array(
				'data_type' => 'datetime',
				'required' => false
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
			elseif(is_string($data['IS_PRIMARY']) && mb_strtoupper(trim($data['IS_PRIMARY'])) === 'Y')
			{
				$isPrimary = 'Y';
			}
		}

		$scope = isset($data['SCOPE']) ? $sqlHelper->forSql($data['SCOPE'], 6) : '';

		$dateModify = (isset($data['DATE_MODIFY']) && $data['DATE_MODIFY'] instanceof Main\Type\DateTime) ? $data['DATE_MODIFY'] : new Main\Type\DateTime();
		$dateModify = $sqlHelper->convertToDbDateTime($dateModify);

		$connection->queryExecute(
			"INSERT INTO b_crm_dp_entity_hash(ENTITY_ID, ENTITY_TYPE_ID, TYPE_ID, MATCH_HASH, SCOPE, IS_PRIMARY, DATE_MODIFY)
				VALUES({$entityID}, {$entityTypeID}, {$typeID}, '{$matchHash}', '{$scope}', '{$isPrimary}', {$dateModify})
				ON DUPLICATE KEY UPDATE IS_PRIMARY = '{$isPrimary}'"
		);

	}
	public static function deleteByFilter(array $filter)
	{
		$conditions = self::buildSqlConditions($filter);

		if(!empty($conditions))
		{
			Main\Application::getConnection()->queryExecute('DELETE FROM  b_crm_dp_entity_hash WHERE '.implode(' AND ', $conditions));
		}
	}

	public static function setDateModify(array $filter, Main\Type\DateTime $date)
	{
		$conditions = self::buildSqlConditions($filter);
		if(!empty($conditions))
		{
			$sqlDate = $date->format('Y-m-d H:i:s');
			$conditionSql = implode(' AND ', $conditions);
			Main\Application::getConnection()->queryExecute("UPDATE b_crm_dp_entity_hash SET DATE_MODIFY='{$sqlDate}' WHERE {$conditionSql}");
		}
	}

	protected static function buildSqlConditions(array $filter): array
	{
		$conditions = [];

		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

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

		return $conditions;
	}
}