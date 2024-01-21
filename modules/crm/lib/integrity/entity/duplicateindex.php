<?php

namespace Bitrix\Crm\Integrity\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Crm\Integrity\DuplicateStatus;

Loc::loadMessages(__FILE__);

/**
 * Class DuplicateIndexTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DuplicateIndex_Query query()
 * @method static EO_DuplicateIndex_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DuplicateIndex_Result getById($id)
 * @method static EO_DuplicateIndex_Result getList(array $parameters = [])
 * @method static EO_DuplicateIndex_Entity getEntity()
 * @method static \Bitrix\Crm\Integrity\Entity\EO_DuplicateIndex createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Integrity\Entity\EO_DuplicateIndex_Collection createCollection()
 * @method static \Bitrix\Crm\Integrity\Entity\EO_DuplicateIndex wakeUpObject($row)
 * @method static \Bitrix\Crm\Integrity\Entity\EO_DuplicateIndex_Collection wakeUpCollection($rows)
 */
class DuplicateIndexTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_index';
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
			'SCOPE' => array(
				'data_type' => 'string',
				'primary' => true,
				'required' => true
			),
			'MATCHES' => array(
				'data_type' => 'string',
				'required' => false
			),
			'QUANTITY' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'ROOT_ENTITY_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'ROOT_ENTITY_NAME_FLAG' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_NAME' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_TITLE_FLAG' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_TITLE' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_PHONE_FLAG' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_PHONE' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_EMAIL_FLAG' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_EMAIL' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_INN_FLAG' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_INN' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_OGRN_FLAG' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_OGRN' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_OGRNIP_FLAG' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_OGRNIP' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_BIN_FLAG' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_BIN' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_EDRPOU_FLAG' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_EDRPOU' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_VAT_ID_FLAG' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_VAT_ID' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_ACC_NUM_FLAG' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_ACC_NUM' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_IBAN_FLAG' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_IBAN' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_IIK_FLAG' => array(
				'data_type' => 'string',
				'required' => false
			),
			'ROOT_ENTITY_RQ_IIK' => array(
				'data_type' => 'string',
				'required' => false
			),
			'IS_JUNK' => array(
				'data_type' => 'string',
				'required' => false
			),
			'STATUS_ID' => array(
				'data_type' => 'integer',
				'required' => false
			)
		);
	}

	public static function exists($primary)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$userID = isset($primary['USER_ID']) ? intval($primary['USER_ID']) : 0;
		$entityTypeID = isset($primary['ENTITY_TYPE_ID']) ? intval($primary['ENTITY_TYPE_ID']) : 0;
		$typeID = isset($primary['TYPE_ID']) ? intval($primary['TYPE_ID']) : 0;
		$matchHash = isset($primary['MATCH_HASH']) ? $sqlHelper->forSql($primary['MATCH_HASH'], 32) : '';
		$scope = isset($primary['SCOPE']) ? $primary['SCOPE'] : DuplicateIndexType::DEFAULT_SCOPE;

		$dbResult = $connection->query(
			"SELECT 'X' FROM b_crm_dp_index WHERE USER_ID = {$userID} AND ENTITY_TYPE_ID = {$entityTypeID} AND TYPE_ID = {$typeID} AND SCOPE = '{$scope}' AND MATCH_HASH = '{$matchHash}'"
		);
		return is_array($dbResult->fetch());
	}

	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$userID = (int)($data['USER_ID'] ?? 0);
		$entityTypeID = (int)($data['ENTITY_TYPE_ID'] ?? 0);
		$typeID = (int)($data['TYPE_ID'] ?? 0);
		$matchHash = mb_substr((string)($data['MATCH_HASH'] ?? ''), 0, 32);
		$scope = $data['SCOPE'] ?? DuplicateIndexType::DEFAULT_SCOPE;
		$matches = $data['MATCHES'] ?? '';
		$qty = (int)($data['QUANTITY'] ?? 0);
		$statusID = (int)($data['STATUS_ID'] ?? DuplicateStatus::UNDEFINED);
		$rootEntityID = (int)($data['ROOT_ENTITY_ID'] ?? 0);
		$rootEntityName = mb_substr((string)($data['ROOT_ENTITY_NAME'] ?? ''), 0, 256);
		$rootEntityTitle = mb_substr((string)($data['ROOT_ENTITY_TITLE'] ?? ''), 0, 256);
		$rootEntityPhone = mb_substr((string)($data['ROOT_ENTITY_PHONE'] ?? ''), 0, 256);
		$rootEntityEmail = mb_substr((string)($data['ROOT_ENTITY_EMAIL'] ?? ''), 0, 256);
		$rootEntityRqInn = mb_substr((string)($data['ROOT_ENTITY_RQ_INN'] ?? ''), 0, 15);
		$rootEntityRqOgrn = mb_substr((string)($data['ROOT_ENTITY_RQ_OGRN'] ?? ''), 0, 13);
		$rootEntityRqOgrnip = mb_substr((string)($data['ROOT_ENTITY_RQ_OGRNIP'] ?? ''), 0, 15);
		$rootEntityRqBin = mb_substr((string)($data['ROOT_ENTITY_RQ_BIN'] ?? ''), 0, 12);
		$rootEntityRqEdrpou = mb_substr((string)($data['ROOT_ENTITY_RQ_EDRPOU'] ?? ''), 0, 10);
		$rootEntityRqVatId = mb_substr((string)($data['ROOT_ENTITY_RQ_VAT_ID'] ?? ''), 0, 20);
		$rootEntityRqAccNum = mb_substr((string)($data['ROOT_ENTITY_RQ_ACC_NUM'] ?? ''), 0, 34);
		$rootEntityRqIban = mb_substr((string)($data['ROOT_ENTITY_RQ_IBAN'] ?? ''), 0, 34);
		$rootEntityRqIik = mb_substr((string)($data['ROOT_ENTITY_RQ_IIK'] ?? ''), 0, 20);

		$rootEntityNameFlg = $rootEntityName !== '' ? '0' : '1';
		$rootEntityTitleFlg = $rootEntityTitle !== '' ? '0' : '1';
		$rootEntityPhoneFlg = $rootEntityPhone !== '' ? '0' : '1';
		$rootEntityEmailFlg = $rootEntityEmail !== '' ? '0' : '1';
		$rootEntityRqInnFlg = $rootEntityRqInn !== '' ? '0' : '1';
		$rootEntityRqOgrnFlg = $rootEntityRqOgrn !== '' ? '0' : '1';
		$rootEntityRqOgrnipFlg = $rootEntityRqOgrnip !== '' ? '0' : '1';
		$rootEntityRqBinFlg = $rootEntityRqBin !== '' ? '0' : '1';
		$rootEntityRqEdrpouFlg = $rootEntityRqEdrpou !== '' ? '0' : '1';
		$rootEntityRqVatIdFlg = $rootEntityRqVatId !== '' ? '0' : '1';
		$rootEntityRqAccNumFlg = $rootEntityRqAccNum !== '' ? '0' : '1';
		$rootEntityRqIbanFlg = $rootEntityRqIban !== '' ? '0' : '1';
		$rootEntityRqIikFlg = $rootEntityRqIik !== '' ? '0' : '1';

		$sql = $sqlHelper->prepareMerge(
			'b_crm_dp_index',
			[
				'USER_ID',
				'ENTITY_TYPE_ID',
				'TYPE_ID',
				'MATCH_HASH',
				'SCOPE',
			],
			[
				'USER_ID' => $userID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'TYPE_ID' => $typeID,
				'SCOPE' => $scope,
				'MATCH_HASH' => $matchHash,
				'MATCHES' => $matches,
				'QUANTITY' => $qty,
				'ROOT_ENTITY_ID' => $rootEntityID,
				'ROOT_ENTITY_NAME_FLAG' => $rootEntityNameFlg,
				'ROOT_ENTITY_NAME' => $rootEntityName,
				'ROOT_ENTITY_TITLE_FLAG' => $rootEntityTitleFlg,
				'ROOT_ENTITY_TITLE' => $rootEntityTitle,
				'ROOT_ENTITY_PHONE_FLAG' => $rootEntityPhoneFlg,
				'ROOT_ENTITY_PHONE' => $rootEntityPhone,
				'ROOT_ENTITY_EMAIL_FLAG' => $rootEntityEmailFlg,
				'ROOT_ENTITY_EMAIL' => $rootEntityEmail,
				'ROOT_ENTITY_RQ_INN_FLAG' => $rootEntityRqInnFlg,
				'ROOT_ENTITY_RQ_INN' => $rootEntityRqInn,
				'ROOT_ENTITY_RQ_OGRN_FLAG' => $rootEntityRqOgrnFlg,
				'ROOT_ENTITY_RQ_OGRN' => $rootEntityRqOgrn,
				'ROOT_ENTITY_RQ_OGRNIP_FLAG' => $rootEntityRqOgrnipFlg,
				'ROOT_ENTITY_RQ_OGRNIP' => $rootEntityRqOgrnip,
				'ROOT_ENTITY_RQ_BIN_FLAG' => $rootEntityRqBinFlg,
				'ROOT_ENTITY_RQ_BIN' => $rootEntityRqBin,
				'ROOT_ENTITY_RQ_EDRPOU_FLAG' => $rootEntityRqEdrpouFlg,
				'ROOT_ENTITY_RQ_EDRPOU' => $rootEntityRqEdrpou,
				'ROOT_ENTITY_RQ_VAT_ID_FLAG' => $rootEntityRqVatIdFlg,
				'ROOT_ENTITY_RQ_VAT_ID' => $rootEntityRqVatId,
				'ROOT_ENTITY_RQ_ACC_NUM_FLAG' => $rootEntityRqAccNumFlg,
				'ROOT_ENTITY_RQ_ACC_NUM' => $rootEntityRqAccNum,
				'ROOT_ENTITY_RQ_IBAN_FLAG' => $rootEntityRqIbanFlg,
				'ROOT_ENTITY_RQ_IBAN' => $rootEntityRqIban,
				'ROOT_ENTITY_RQ_IIK_FLAG' => $rootEntityRqIikFlg,
				'ROOT_ENTITY_RQ_IIK' => $rootEntityRqIik,
				'STATUS_ID' => $statusID,
			],
			[
				'QUANTITY' => $qty,
				'ROOT_ENTITY_ID' => $rootEntityID,
				'ROOT_ENTITY_NAME_FLAG' => $rootEntityNameFlg,
				'ROOT_ENTITY_NAME' => $rootEntityName,
				'ROOT_ENTITY_TITLE_FLAG' => $rootEntityTitleFlg,
				'ROOT_ENTITY_TITLE' => $rootEntityTitle,
				'ROOT_ENTITY_PHONE_FLAG' => $rootEntityPhoneFlg,
				'ROOT_ENTITY_PHONE' => $rootEntityPhone,
				'ROOT_ENTITY_EMAIL_FLAG' => $rootEntityEmailFlg,
				'ROOT_ENTITY_EMAIL' => $rootEntityEmail,
				'ROOT_ENTITY_RQ_INN_FLAG' => $rootEntityRqInnFlg,
				'ROOT_ENTITY_RQ_INN' => $rootEntityRqInn,
				'ROOT_ENTITY_RQ_OGRN_FLAG' => $rootEntityRqOgrnFlg,
				'ROOT_ENTITY_RQ_OGRN' => $rootEntityRqOgrn,
				'ROOT_ENTITY_RQ_OGRNIP_FLAG' => $rootEntityRqOgrnipFlg,
				'ROOT_ENTITY_RQ_OGRNIP' => $rootEntityRqOgrnip,
				'ROOT_ENTITY_RQ_BIN_FLAG' => $rootEntityRqBinFlg,
				'ROOT_ENTITY_RQ_BIN' => $rootEntityRqBin,
				'ROOT_ENTITY_RQ_EDRPOU_FLAG' => $rootEntityRqEdrpouFlg,
				'ROOT_ENTITY_RQ_EDRPOU' => $rootEntityRqEdrpou,
				'ROOT_ENTITY_RQ_VAT_ID_FLAG' => $rootEntityRqVatIdFlg,
				'ROOT_ENTITY_RQ_VAT_ID' => $rootEntityRqVatId,
				'ROOT_ENTITY_RQ_ACC_NUM_FLAG' => $rootEntityRqAccNumFlg,
				'ROOT_ENTITY_RQ_ACC_NUM' => $rootEntityRqAccNum,
				'ROOT_ENTITY_RQ_IBAN_FLAG' => $rootEntityRqIbanFlg,
				'ROOT_ENTITY_RQ_IBAN' => $rootEntityRqIban,
				'ROOT_ENTITY_RQ_IIK_FLAG' => $rootEntityRqIikFlg,
				'ROOT_ENTITY_RQ_IIK' => $rootEntityRqIik,
				'STATUS_ID' => $statusID,
			]
		);

		$connection->queryExecute($sql[0]);
	}

	public static function deleteByFilter(array $filter)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$conditions = array();
		if (isset($filter['USER_ID']))
		{
			$userID = intval($filter['USER_ID']);
			$conditions[] = "USER_ID = {$userID}";
		}

		if (isset($filter['ENTITY_TYPE_ID']))
		{
			$entityTypeID = intval($filter['ENTITY_TYPE_ID']);
			$conditions[] = "ENTITY_TYPE_ID = {$entityTypeID}";
		}

		if (isset($filter['TYPE_ID']))
		{
			$typeID = intval($filter['TYPE_ID']);
			$conditions[] = "TYPE_ID = {$typeID}";
		}

		if (isset($filter['SCOPE']))
		{
			$scope = $sqlHelper->forSql($filter['SCOPE']);
			$conditions[] = "SCOPE = '{$scope}'";
		}

		if (isset($filter['MATCH_HASH']))
		{
			$matchHash = $sqlHelper->forSql($filter['MATCH_HASH']);
			$conditions[] = "MATCH_HASH = '{$matchHash}'";
		}

		if (isset($data['QUANTITY']))
		{
			$quantity = intval($filter['QUANTITY']);
			$conditions[] = "QUANTITY = {$quantity}";
		}

		if (!empty($conditions))
		{
			$conditionSql = implode(' AND ', $conditions);
			Main\Application::getConnection()->queryExecute("DELETE FROM  b_crm_dp_index WHERE {$conditionSql}");
		}
	}

	public static function markAsJunk($entityTypeID, $entityID)
	{
		Main\Application::getConnection()->queryExecute(
			"UPDATE b_crm_dp_index SET IS_JUNK = 'Y' WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ROOT_ENTITY_ID = {$entityID}"
		);
	}

	public static function setStatusID(
		int $userID,
		int $entityTypeID,
		int $typeID,
		string $matchHash,
		string $scope,
		int $statusID
	)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$matchHash = $sqlHelper->forSql($matchHash);
		$scope = $sqlHelper->forSql($scope);

		Main\Application::getConnection()->queryExecute(
			"UPDATE b_crm_dp_index SET STATUS_ID = {$statusID} " .
			"WHERE USER_ID = {$userID} AND ENTITY_TYPE_ID = {$entityTypeID} AND TYPE_ID = {$typeID} AND MATCH_HASH = '{$matchHash}' AND SCOPE = '{$scope}'"
		);
	}
}