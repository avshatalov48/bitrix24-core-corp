<?php
namespace Bitrix\Crm\Integrity\Entity;

use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

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
		$scope = isset($primary['SCOPE']) ?  $primary['SCOPE'] : DuplicateIndexType::DEFAULT_SCOPE;

		$dbResult = $connection->query(
			"SELECT 'X' FROM b_crm_dp_index WHERE USER_ID = {$userID} AND ENTITY_TYPE_ID = {$entityTypeID} AND TYPE_ID = {$typeID} AND SCOPE = '{$scope}' AND MATCH_HASH = '{$matchHash}'"
		);
		return is_array($dbResult->fetch());
	}
	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$userID = isset($data['USER_ID']) ? intval($data['USER_ID']) : 0;
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? intval($data['ENTITY_TYPE_ID']) : 0;
		$typeID = isset($data['TYPE_ID']) ? intval($data['TYPE_ID']) : 0;
		$matchHash = isset($data['MATCH_HASH']) ? $sqlHelper->forSql($data['MATCH_HASH'], 32) : '';
		$scope = isset($data['SCOPE']) ?  $data['SCOPE'] : DuplicateIndexType::DEFAULT_SCOPE;

		$matches = isset($data['MATCHES']) ? $sqlHelper->forSql($data['MATCHES']) : '';
		$qty = isset($data['QUANTITY']) ? intval($data['QUANTITY']) : 0;

		$rootEntityID = isset($data['ROOT_ENTITY_ID']) ? intval($data['ROOT_ENTITY_ID']) : 0;
		$rootEntityName = isset($data['ROOT_ENTITY_NAME']) ? $sqlHelper->forSql($data['ROOT_ENTITY_NAME'], 256) : '';
		$rootEntityTitle = isset($data['ROOT_ENTITY_TITLE']) ? $sqlHelper->forSql($data['ROOT_ENTITY_TITLE'], 256) : '';
		$rootEntityPhone = isset($data['ROOT_ENTITY_PHONE']) ? $sqlHelper->forSql($data['ROOT_ENTITY_PHONE'], 256) : '';
		$rootEntityEmail = isset($data['ROOT_ENTITY_EMAIL']) ? $sqlHelper->forSql($data['ROOT_ENTITY_EMAIL'], 256) : '';
		$rootEntityRqInn = isset($data['ROOT_ENTITY_RQ_INN']) ? $sqlHelper->forSql($data['ROOT_ENTITY_RQ_INN'], 15) : '';
		$rootEntityRqOgrn = isset($data['ROOT_ENTITY_RQ_OGRN']) ? $sqlHelper->forSql($data['ROOT_ENTITY_RQ_OGRN'], 13) : '';
		$rootEntityRqOgrnip = isset($data['ROOT_ENTITY_RQ_OGRNIP']) ? $sqlHelper->forSql($data['ROOT_ENTITY_RQ_OGRNIP'], 15) : '';
		$rootEntityRqBin = isset($data['ROOT_ENTITY_RQ_BIN']) ? $sqlHelper->forSql($data['ROOT_ENTITY_RQ_BIN'], 12) : '';
		$rootEntityRqEdrpou = isset($data['ROOT_ENTITY_RQ_EDRPOU']) ? $sqlHelper->forSql($data['ROOT_ENTITY_RQ_EDRPOU'], 10) : '';
		$rootEntityRqVatId = isset($data['ROOT_ENTITY_RQ_VAT_ID']) ? $sqlHelper->forSql($data['ROOT_ENTITY_RQ_VAT_ID'], 20) : '';
		$rootEntityRqAccNum = isset($data['ROOT_ENTITY_RQ_ACC_NUM']) ? $sqlHelper->forSql($data['ROOT_ENTITY_RQ_ACC_NUM'], 34) : '';
		$rootEntityRqIban = isset($data['ROOT_ENTITY_RQ_IBAN']) ? $sqlHelper->forSql($data['ROOT_ENTITY_RQ_IBAN'], 34) : '';
		$rootEntityRqIik = isset($data['ROOT_ENTITY_RQ_IIK']) ? $sqlHelper->forSql($data['ROOT_ENTITY_RQ_IIK'], 20) : '';

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

		if($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$connection->queryExecute(
				"INSERT INTO b_crm_dp_index(USER_ID, ENTITY_TYPE_ID, TYPE_ID, SCOPE, MATCH_HASH, MATCHES, QUANTITY, ROOT_ENTITY_ID, ".
					"ROOT_ENTITY_NAME_FLAG, ROOT_ENTITY_NAME, ROOT_ENTITY_TITLE_FLAG, ROOT_ENTITY_TITLE, ".
					"ROOT_ENTITY_PHONE_FLAG, ROOT_ENTITY_PHONE, ROOT_ENTITY_EMAIL_FLAG, ROOT_ENTITY_EMAIL, ".
					"ROOT_ENTITY_RQ_INN_FLAG, ROOT_ENTITY_RQ_INN, ROOT_ENTITY_RQ_OGRN_FLAG, ROOT_ENTITY_RQ_OGRN, ".
					"ROOT_ENTITY_RQ_OGRNIP_FLAG, ROOT_ENTITY_RQ_OGRNIP, ROOT_ENTITY_RQ_BIN_FLAG, ROOT_ENTITY_RQ_BIN, ".
					"ROOT_ENTITY_RQ_EDRPOU_FLAG, ROOT_ENTITY_RQ_EDRPOU, ROOT_ENTITY_RQ_VAT_ID_FLAG, ROOT_ENTITY_RQ_VAT_ID, ".
					"ROOT_ENTITY_RQ_ACC_NUM_FLAG, ROOT_ENTITY_RQ_ACC_NUM, ROOT_ENTITY_RQ_IBAN_FLAG, ROOT_ENTITY_RQ_IBAN, ".
					"ROOT_ENTITY_RQ_IIK_FLAG, ROOT_ENTITY_RQ_IIK)
				VALUES({$userID}, {$entityTypeID}, {$typeID}, '{$scope}', '{$matchHash}', '{$matches}', {$qty}, {$rootEntityID}, ".
					"'{$rootEntityNameFlg}', '{$rootEntityName}', '{$rootEntityTitleFlg}', '{$rootEntityTitle}', ".
					"'{$rootEntityPhoneFlg}', '{$rootEntityPhone}', '{$rootEntityEmailFlg}', '{$rootEntityEmail}', ".
					"'{$rootEntityRqInnFlg}', '{$rootEntityRqInn}', '{$rootEntityRqOgrnFlg}', '{$rootEntityRqOgrn}', ".
					"'{$rootEntityRqOgrnipFlg}', '{$rootEntityRqOgrnip}', '{$rootEntityRqBinFlg}', '{$rootEntityRqBin}', ".
					"'{$rootEntityRqEdrpouFlg}', '{$rootEntityRqEdrpou}', '{$rootEntityRqVatIdFlg}', '{$rootEntityRqVatId}', ".
					"'{$rootEntityRqAccNumFlg}', '{$rootEntityRqAccNum}', '{$rootEntityRqIbanFlg}', '{$rootEntityRqIban}', ".
					"'{$rootEntityRqIikFlg}', '{$rootEntityRqIik}')
				ON DUPLICATE KEY UPDATE QUANTITY = {$qty}, ROOT_ENTITY_ID = {$rootEntityID}, ".
					"ROOT_ENTITY_NAME_FLAG = '{$rootEntityNameFlg}', ROOT_ENTITY_NAME = '{$rootEntityName}', ".
					"ROOT_ENTITY_TITLE_FLAG = '{$rootEntityTitleFlg}', ROOT_ENTITY_TITLE = '{$rootEntityTitle}', ".
					"ROOT_ENTITY_PHONE_FLAG = '{$rootEntityPhoneFlg}', ROOT_ENTITY_PHONE = '{$rootEntityPhone}', ".
					"ROOT_ENTITY_EMAIL_FLAG = '{$rootEntityEmailFlg}', ROOT_ENTITY_EMAIL = '{$rootEntityEmail}', ".
					"ROOT_ENTITY_RQ_INN_FLAG = '{$rootEntityRqInnFlg}', ROOT_ENTITY_RQ_INN = '{$rootEntityRqInn}', ".
					"ROOT_ENTITY_RQ_OGRN_FLAG = '{$rootEntityRqOgrnFlg}', ROOT_ENTITY_RQ_OGRN = '{$rootEntityRqOgrn}', ".
					"ROOT_ENTITY_RQ_OGRNIP_FLAG = '{$rootEntityRqOgrnipFlg}', ROOT_ENTITY_RQ_OGRNIP = '{$rootEntityRqOgrnip}', ".
					"ROOT_ENTITY_RQ_BIN_FLAG = '{$rootEntityRqBinFlg}', ROOT_ENTITY_RQ_BIN = '{$rootEntityRqBin}', ".
					"ROOT_ENTITY_RQ_EDRPOU_FLAG = '{$rootEntityRqEdrpouFlg}', ROOT_ENTITY_RQ_EDRPOU = '{$rootEntityRqEdrpou}', ".
					"ROOT_ENTITY_RQ_VAT_ID_FLAG = '{$rootEntityRqVatIdFlg}', ROOT_ENTITY_RQ_VAT_ID = '{$rootEntityRqVatId}', ".
					"ROOT_ENTITY_RQ_ACC_NUM_FLAG = '{$rootEntityRqAccNumFlg}', ROOT_ENTITY_RQ_ACC_NUM = '{$rootEntityRqAccNum}', ".
					"ROOT_ENTITY_RQ_IBAN_FLAG = '{$rootEntityRqIbanFlg}', ROOT_ENTITY_RQ_IBAN = '{$rootEntityRqIban}', ".
					"ROOT_ENTITY_RQ_IIK_FLAG = '{$rootEntityRqIikFlg}', ROOT_ENTITY_RQ_IIK = '{$rootEntityRqIik}'"
			);
		}
		elseif($connection instanceof Main\DB\MssqlConnection)
		{
			$dbResult = $connection->query(
				"SELECT 'X' FROM b_crm_dp_index WHERE USER_ID = {$userID} AND ENTITY_TYPE_ID = {$entityTypeID} ".
					"AND TYPE_ID = {$typeID} AND SCOPE = '{$scope}' AND MATCH_HASH = '{$matchHash}'"
			);

			if(is_array($dbResult->fetch()))
			{
				$connection->queryExecute(
					"UPDATE b_crm_dp_index SET QUANTITY = {$qty}, ROOT_ENTITY_ID = {$rootEntityID}, ".
						"ROOT_ENTITY_NAME_FLAG = '{$rootEntityNameFlg}', ROOT_ENTITY_NAME = '{$rootEntityName}', ".
						"ROOT_ENTITY_TITLE_FLAG = '{$rootEntityTitleFlg}', ROOT_ENTITY_TITLE = '{$rootEntityTitle}', ".
						"ROOT_ENTITY_PHONE_FLAG = '{$rootEntityPhoneFlg}', ROOT_ENTITY_PHONE = '{$rootEntityPhone}', ".
						"ROOT_ENTITY_EMAIL_FLAG = '{$rootEntityEmailFlg}', ROOT_ENTITY_EMAIL = '{$rootEntityEmail}', ".
						"ROOT_ENTITY_RQ_INN_FLAG = '{$rootEntityRqInnFlg}', ROOT_ENTITY_RQ_INN = '{$rootEntityRqInn}', ".
						"ROOT_ENTITY_RQ_OGRN_FLAG = '{$rootEntityRqOgrnFlg}', ROOT_ENTITY_RQ_OGRN = '{$rootEntityRqOgrn}', ".
						"ROOT_ENTITY_RQ_OGRNIP_FLAG = '{$rootEntityRqOgrnipFlg}', ROOT_ENTITY_RQ_OGRNIP = '{$rootEntityRqOgrnip}', ".
						"ROOT_ENTITY_RQ_BIN_FLAG = '{$rootEntityRqBinFlg}', ROOT_ENTITY_RQ_BIN = '{$rootEntityRqBin}', ".
						"ROOT_ENTITY_RQ_EDRPOU_FLAG = '{$rootEntityRqEdrpouFlg}', ROOT_ENTITY_RQ_EDRPOU = '{$rootEntityRqEdrpou}', ".
						"ROOT_ENTITY_RQ_VAT_ID_FLAG = '{$rootEntityRqVatIdFlg}', ROOT_ENTITY_RQ_VAT_ID = '{$rootEntityRqVatId}', ".
						"ROOT_ENTITY_RQ_ACC_NUM_FLAG = '{$rootEntityRqAccNumFlg}', ROOT_ENTITY_RQ_ACC_NUM = '{$rootEntityRqAccNum}', ".
						"ROOT_ENTITY_RQ_IBAN_FLAG = '{$rootEntityRqIbanFlg}', ROOT_ENTITY_RQ_IBAN = '{$rootEntityRqIban}', ".
						"ROOT_ENTITY_RQ_IIK_FLAG = '{$rootEntityRqIikFlg}', ROOT_ENTITY_RQ_IIK = '{$rootEntityRqIik}'
					WHERE USER_ID = {$userID} AND ENTITY_TYPE_ID = {$entityTypeID} AND TYPE_ID = {$typeID} AND ".
						"SCOPE = '{$scope}' AND MATCH_HASH = '{$matchHash}'"
				);
			}
			else
			{
				$connection->queryExecute(
					"INSERT INTO b_crm_dp_index(USER_ID, ENTITY_TYPE_ID, TYPE_ID, SCOPE, MATCH_HASH, MATCHES, QUANTITY, ROOT_ENTITY_ID, ".
						"ROOT_ENTITY_NAME_FLAG, ROOT_ENTITY_NAME, ROOT_ENTITY_TITLE_FLAG, ROOT_ENTITY_TITLE, ".
						"ROOT_ENTITY_PHONE_FLAG, ROOT_ENTITY_PHONE, ROOT_ENTITY_EMAIL_FLAG, ROOT_ENTITY_EMAIL, ".
						"ROOT_ENTITY_RQ_INN_FLAG, ROOT_ENTITY_RQ_INN, ROOT_ENTITY_RQ_OGRN_FLAG, ROOT_ENTITY_RQ_OGRN, ".
						"ROOT_ENTITY_RQ_OGRNIP_FLAG, ROOT_ENTITY_RQ_OGRNIP, ROOT_ENTITY_RQ_BIN_FLAG, ROOT_ENTITY_RQ_BIN, ".
						"ROOT_ENTITY_RQ_EDRPOU_FLAG, ROOT_ENTITY_RQ_EDRPOU, ROOT_ENTITY_RQ_VAT_ID_FLAG, ROOT_ENTITY_RQ_VAT_ID, ".
						"ROOT_ENTITY_RQ_ACC_NUM_FLAG, ROOT_ENTITY_RQ_ACC_NUM, ROOT_ENTITY_RQ_IBAN_FLAG, ROOT_ENTITY_RQ_IBAN, ".
						"ROOT_ENTITY_RQ_IIK_FLAG, ROOT_ENTITY_RQ_IIK)
					VALUES({$userID}, {$entityTypeID}, {$scope}, '{$matchHash}', '{$matches}', {$qty}, {$rootEntityID}, ".
						"'{$rootEntityNameFlg}', '{$rootEntityName}', '{$rootEntityTitleFlg}', '{$rootEntityTitle}', ".
						"'{$rootEntityPhoneFlg}', '{$rootEntityPhone}', '{$rootEntityEmailFlg}', '{$rootEntityEmail}', ".
						"'{$rootEntityRqInnFlg}', '{$rootEntityRqInn}', '{$rootEntityRqOgrnFlg}', '{$rootEntityRqOgrn}', ".
						"'{$rootEntityRqOgrnipFlg}', '{$rootEntityRqOgrnip}', '{$rootEntityRqBinFlg}', '{$rootEntityRqBin}', ".
						"'{$rootEntityRqEdrpouFlg}', '{$rootEntityRqEdrpou}', '{$rootEntityRqVatIdFlg}', '{$rootEntityRqVatId}', ".
						"'{$rootEntityRqAccNumFlg}', '{$rootEntityRqAccNum}', '{$rootEntityRqIbanFlg}', '{$rootEntityRqIban}', ".
						"'{$rootEntityRqIikFlg}', '{$rootEntityRqIik}')"
				);
			}
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$connection->queryExecute("MERGE INTO b_crm_dp_index USING (SELECT {$userID} USER_ID, ".
				"{$entityTypeID} ENTITY_TYPE_ID, {$typeID} TYPE_ID, {$scope} SCOPE, '{$matchHash}' MATCH_HASH FROM dual)
				source ON
				(
					source.USER_ID = b_crm_dp_index.USER_ID
					AND source.ENTITY_TYPE_ID = b_crm_dp_index.ENTITY_TYPE_ID
					AND source.TYPE_ID = b_crm_dp_index.TYPE_ID
					AND source.SCOPE = b_crm_dp_index.SCOPE
					AND source.MATCH_HASH = b_crm_dp_index.MATCH_HASH
				)
				WHEN MATCHED THEN
					UPDATE SET b_crm_dp_index.QUANTITY = {$qty}, ROOT_ENTITY_ID = {$rootEntityID}, ".
					"ROOT_ENTITY_NAME_FLAG = '{$rootEntityNameFlg}', ROOT_ENTITY_NAME = '{$rootEntityName}', ".
					"ROOT_ENTITY_TITLE_FLAG = '{$rootEntityTitleFlg}', ROOT_ENTITY_TITLE = '{$rootEntityTitle}', ".
					"ROOT_ENTITY_PHONE_FLAG = '{$rootEntityPhoneFlg}', ROOT_ENTITY_PHONE = '{$rootEntityPhone}', ".
					"ROOT_ENTITY_EMAIL_FLAG = '{$rootEntityEmailFlg}', ROOT_ENTITY_EMAIL = '{$rootEntityEmail}', ".
					"ROOT_ENTITY_RQ_INN_FLAG = '{$rootEntityRqInnFlg}', ROOT_ENTITY_RQ_INN = '{$rootEntityRqInn}', ".
					"ROOT_ENTITY_RQ_OGRN_FLAG = '{$rootEntityRqOgrnFlg}', ROOT_ENTITY_RQ_OGRN = '{$rootEntityRqOgrn}', ".
					"ROOT_ENTITY_RQ_OGRNIP_FLAG = '{$rootEntityRqOgrnipFlg}', ROOT_ENTITY_RQ_OGRNIP = '{$rootEntityRqOgrnip}', ".
					"ROOT_ENTITY_RQ_BIN_FLAG = '{$rootEntityRqBinFlg}', ROOT_ENTITY_RQ_BIN = '{$rootEntityRqBin}', ".
					"ROOT_ENTITY_RQ_EDRPOU_FLAG = '{$rootEntityRqEdrpouFlg}', ROOT_ENTITY_RQ_EDRPOU = '{$rootEntityRqEdrpou}', ".
					"ROOT_ENTITY_RQ_VAT_ID_FLAG = '{$rootEntityRqVatIdFlg}', ROOT_ENTITY_RQ_VAT_ID = '{$rootEntityRqVatId}', ".
					"ROOT_ENTITY_RQ_ACC_NUM_FLAG = '{$rootEntityRqAccNumFlg}', ROOT_ENTITY_RQ_ACC_NUM = '{$rootEntityRqAccNum}', ".
					"ROOT_ENTITY_RQ_IBAN_FLAG = '{$rootEntityRqIbanFlg}', ROOT_ENTITY_RQ_IBAN = '{$rootEntityRqIban}', ".
					"ROOT_ENTITY_RQ_IIK_FLAG = '{$rootEntityRqIikFlg}', ROOT_ENTITY_RQ_IIK = '{$rootEntityRqIik}'
				WHEN NOT MATCHED THEN
					INSERT (USER_ID, ENTITY_TYPE_ID, TYPE_ID, SCOPE, MATCH_HASH, MATCHES, QUANTITY, ROOT_ENTITY_ID, ".
						"ROOT_ENTITY_NAME_FLAG, ROOT_ENTITY_NAME, ROOT_ENTITY_TITLE_FLAG, ROOT_ENTITY_TITLE, ".
						"ROOT_ENTITY_PHONE_FLAG, ROOT_ENTITY_PHONE, ROOT_ENTITY_EMAIL_FLAG, ROOT_ENTITY_EMAIL, ".
						"ROOT_ENTITY_RQ_INN_FLAG, ROOT_ENTITY_RQ_INN, ROOT_ENTITY_RQ_OGRN_FLAG, ROOT_ENTITY_RQ_OGRN, ".
						"ROOT_ENTITY_RQ_OGRNIP_FLAG, ROOT_ENTITY_RQ_OGRNIP, ROOT_ENTITY_RQ_BIN_FLAG, ROOT_ENTITY_RQ_BIN, ".
						"ROOT_ENTITY_RQ_EDRPOU_FLAG, ROOT_ENTITY_RQ_EDRPOU, ROOT_ENTITY_RQ_VAT_ID_FLAG, ROOT_ENTITY_RQ_VAT_ID, ".
						"ROOT_ENTITY_RQ_ACC_NUM_FLAG, ROOT_ENTITY_RQ_ACC_NUM, ROOT_ENTITY_RQ_IBAN_FLAG, ROOT_ENTITY_RQ_IBAN, ".
						"ROOT_ENTITY_RQ_IIK_FLAG, ROOT_ENTITY_RQ_IIK)
					VALUES({$userID}, {$entityTypeID}, {$typeID}, {$scope}, '{$matchHash}', '{$matches}', {$qty}, {$rootEntityID}, ".
						"'{$rootEntityNameFlg}', '{$rootEntityName}', '{$rootEntityTitleFlg}', '{$rootEntityTitle}', ".
						"'{$rootEntityPhoneFlg}', '{$rootEntityPhone}', '{$rootEntityEmailFlg}', '{$rootEntityEmail}', ".
						"'{$rootEntityRqInnFlg}', '{$rootEntityRqInn}', '{$rootEntityRqOgrnFlg}', '{$rootEntityRqOgrn}', ".
						"'{$rootEntityRqOgrnipFlg}', '{$rootEntityRqOgrnip}', '{$rootEntityRqBinFlg}', '{$rootEntityRqBin}', ".
						"'{$rootEntityRqEdrpouFlg}', '{$rootEntityRqEdrpou}', '{$rootEntityRqVatIdFlg}', '{$rootEntityRqVatId}', ".
						"'{$rootEntityRqAccNumFlg}', '{$rootEntityRqAccNum}', '{$rootEntityRqIbanFlg}', '{$rootEntityRqIban}', ".
						"'{$rootEntityRqIikFlg}', '{$rootEntityRqIik}')"
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
		if(isset($filter['USER_ID']))
		{
			$userID = intval($filter['USER_ID']);
			$conditions[] = "USER_ID = {$userID}";
		}

		if(isset($filter['ENTITY_TYPE_ID']))
		{
			$entityTypeID = intval($filter['ENTITY_TYPE_ID']);
			$conditions[] = "ENTITY_TYPE_ID = {$entityTypeID}";
		}

		if(isset($filter['TYPE_ID']))
		{
			$typeID = intval($filter['TYPE_ID']);
			$conditions[] = "TYPE_ID = {$typeID}";
		}

		if(isset($filter['SCOPE']))
		{
			$scope = $sqlHelper->forSql($filter['SCOPE']);
			$conditions[] = "SCOPE = '{$scope}'";
		}

		if(isset($filter['MATCH_HASH']))
		{
			$matchHash = $sqlHelper->forSql($filter['MATCH_HASH']);
			$conditions[] = "MATCH_HASH = '{$matchHash}'";
		}

		if(isset($data['QUANTITY']))
		{
			$quantity = intval($filter['QUANTITY']);
			$conditions[] = "QUANTITY = {$quantity}";
		}

		if(!empty($conditions))
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
}