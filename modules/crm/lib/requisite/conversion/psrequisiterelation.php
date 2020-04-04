<?php
namespace Bitrix\Crm\Requisite\Conversion;
use Bitrix\Main;
use Bitrix\Main\Entity;

class PSRequisiteRelationTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_ps_rq_conv_relation';
	}

	public static function getMap()
	{
		return array(
			'ENTITY_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'COMPANY_ID' => array('data_type' => 'integer'),
			'REQUISITE_ID' => array('data_type' => 'integer'),
			'BANK_DETAIL_ID' => array('data_type' => 'integer')
		);
	}

	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();

		$entityId = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;
		$companyId = isset($data['COMPANY_ID']) ? (int)$data['COMPANY_ID'] : 0;
		$requisiteId = isset($data['REQUISITE_ID']) ? (int)$data['REQUISITE_ID'] : 0;
		$bankDetailId = isset($data['BANK_DETAIL_ID']) ? (int)$data['BANK_DETAIL_ID'] : 0;

		if ($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$connection->queryExecute(/** @lang MySQL */
				"INSERT INTO b_crm_ps_rq_conv_relation (ENTITY_ID, COMPANY_ID, REQUISITE_ID, BANK_DETAIL_ID)".PHP_EOL.
				"VALUES ({$entityId}, {$companyId}, {$requisiteId}, {$bankDetailId})".PHP_EOL.
				"ON DUPLICATE KEY UPDATE ".
				"COMPANY_ID = {$companyId}, REQUISITE_ID = {$requisiteId}, BANK_DETAIL_ID = {$bankDetailId}".PHP_EOL
			);
		}
		elseif ($connection instanceof Main\DB\MssqlConnection)
		{
			$dbResult = $connection->query(/** @lang TSQL */
				"SELECT 'X'".PHP_EOL.
				"FROM B_CRM_PS_RQ_CONV_RELATION".PHP_EOL.
				"WHERE ENTITY_ID = {$entityId}".PHP_EOL
			);

			if (is_array($dbResult->fetch()))
			{
				$connection->queryExecute(/** @lang TSQL */
					"UPDATE B_CRM_PS_RQ_CONV_RELATION".PHP_EOL.
					"  SET COMPANY_ID = {$companyId}, REQUISITE_ID = {$requisiteId}, BANK_DETAIL_ID = {$bankDetailId}".PHP_EOL.
					"WHERE ENTITY_ID = {$entityId}".PHP_EOL
				);
			}
			else
			{
				$connection->queryExecute(/** @lang TSQL */
					"INSERT INTO B_CRM_PS_RQ_CONV_RELATION (ENTITY_ID, COMPANY_ID, REQUISITE_ID, BANK_DETAIL_ID)".PHP_EOL.
					"VALUES ({$entityId}, {$companyId}, {$requisiteId}, {$bankDetailId})".PHP_EOL
				);
			}
		}
		elseif ($connection instanceof Main\DB\OracleConnection)
		{
			$connection->queryExecute(/** @lang Oracle */
				"MERGE INTO B_CRM_PS_RQ_CONV_RELATION".PHP_EOL.
				"USING (SELECT {$entityId} ENTITY_ID, {$companyId} COMPANY_ID, {$requisiteId} REQUISITE_ID, ".
				"{$bankDetailId} BANK_DETAIL_ID FROM dual) source".PHP_EOL.
				"ON (".PHP_EOL.
				"	source.ENTITY_ID = B_CRM_PS_RQ_CONV_RELATION.ENTITY_ID".PHP_EOL.
				")".PHP_EOL.
				"WHEN MATCHED THEN".PHP_EOL.
				"  UPDATE SET B_CRM_PS_RQ_CONV_RELATION.COMPANY_ID = {$companyId}, ".
				"B_CRM_PS_RQ_CONV_RELATION.REQUISITE_ID = {$requisiteId}, ".
				"B_CRM_PS_RQ_CONV_RELATION.BANK_DETAIL_ID = {$bankDetailId}".PHP_EOL.
				"WHEN NOT MATCHED THEN".PHP_EOL.
				"  INSERT (ENTITY_ID, COMPANY_ID, REQUISITE_ID, BANK_DETAIL_ID)".PHP_EOL.
				"  VALUES ({$entityId}, {$companyId}, {$requisiteId}, {$bankDetailId})".PHP_EOL
			);
		}
		else
		{
			$dbType = $connection->getType();
			throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
		}
	}
}
