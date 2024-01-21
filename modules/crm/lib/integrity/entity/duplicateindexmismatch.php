<?php
namespace Bitrix\Crm\Integrity\Entity;

use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class DuplicateIndexMismatchTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DuplicateIndexMismatch_Query query()
 * @method static EO_DuplicateIndexMismatch_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DuplicateIndexMismatch_Result getById($id)
 * @method static EO_DuplicateIndexMismatch_Result getList(array $parameters = [])
 * @method static EO_DuplicateIndexMismatch_Entity getEntity()
 * @method static \Bitrix\Crm\Integrity\Entity\EO_DuplicateIndexMismatch createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Integrity\Entity\EO_DuplicateIndexMismatch_Collection createCollection()
 * @method static \Bitrix\Crm\Integrity\Entity\EO_DuplicateIndexMismatch wakeUpObject($row)
 * @method static \Bitrix\Crm\Integrity\Entity\EO_DuplicateIndexMismatch_Collection wakeUpCollection($rows)
 */
class DuplicateIndexMismatchTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_index_mismatch';
	}
	public static function getMap()
	{
		return array(
			'USER_ID' => array(
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
			'L_ENTITY_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'required' => true
			),
			'R_ENTITY_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'required' => true
			),
			'SCOPE' => array(
				'data_type' => 'string',
				'primary' => true,
				'required' => true
			)
		);
	}
	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$userID = isset($data['USER_ID']) ? intval($data['USER_ID']) : 0;
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? intval($data['ENTITY_TYPE_ID']) : 0;
		$typeID = isset($data['TYPE_ID']) ? intval($data['TYPE_ID']) : 0;
		$matchHash = isset($data['MATCH_HASH']) ? $sqlHelper->forSql($data['MATCH_HASH'], 32) : '';
		$leftEntityID = isset($data['L_ENTITY_ID']) ? intval($data['L_ENTITY_ID']) : 0;
		$rightEntityID = isset($data['R_ENTITY_ID']) ? intval($data['R_ENTITY_ID']) : 0;
		$scope = (isset($data['SCOPE'])
			&& $data['SCOPE'] !== DuplicateIndexType::DEFAULT_SCOPE
			&& DuplicateIndexType::checkScopeValue($data['SCOPE'])) ?
			mb_substr($data['SCOPE'], 0, 6) : DuplicateIndexType::DEFAULT_SCOPE;

		$sql = $sqlHelper->prepareMerge(
			'b_crm_dp_index_mismatch',
			[
				'USER_ID',
				'ENTITY_TYPE_ID',
				'TYPE_ID',
				'MATCH_HASH',
				'L_ENTITY_ID',
				'R_ENTITY_ID',
				'SCOPE',
			],
			[
				'USER_ID' => $userID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'TYPE_ID' => $typeID,
				'MATCH_HASH' => $matchHash,
				'L_ENTITY_ID' => $leftEntityID,
				'R_ENTITY_ID' => $rightEntityID,
				'SCOPE' => $scope,
			],
			[
				'L_ENTITY_ID' => $leftEntityID,
				'R_ENTITY_ID' => $rightEntityID,
				'SCOPE' => $scope,
			]
		);
		$connection->queryExecute($sql[0]);
	}
	public static function deleteByEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException("Is not defined or invalid", 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException("Must be greater than zero", 'entityID');
		}

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
			"DELETE FROM b_crm_dp_index_mismatch WHERE ENTITY_TYPE_ID = {$entityTypeID} AND L_ENTITY_ID = {$entityID}"
		);
		$connection->queryExecute(
			"DELETE FROM b_crm_dp_index_mismatch WHERE ENTITY_TYPE_ID = {$entityTypeID} AND R_ENTITY_ID = {$entityID}"
		);
	}
}