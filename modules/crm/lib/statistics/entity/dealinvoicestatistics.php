<?php
namespace Bitrix\Crm\Statistics\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Type\Date;

class DealInvoiceStatisticsTable  extends Entity\DataManager
{
	/**
	* @return string
	*/
	public static function getTableName()
	{
		return 'b_crm_deal_inv_stat';
	}
	/**
	* @return array
	*/
	public static function getMap()
	{
		return array(
			'OWNER_ID' => array('data_type' => 'integer', 'required' => true, 'primary' => true),
			'CREATED_DATE' => array('data_type' => 'date', 'required' => true, 'primary' => true),
			'START_DATE' => array('data_type' => 'date', 'required' => true),
			'END_DATE' => array('data_type' => 'date', 'required' => true),
			'PERIOD_YEAR' => array('data_type' => 'integer'),
			'PERIOD_QUARTER' => array('data_type' => 'integer'),
			'PERIOD_MONTH' => array('data_type' => 'integer'),
			'PERIOD_DAY' => array('data_type' => 'integer'),
			'RESPONSIBLE_ID' => array('data_type' => 'integer'),
			'CATEGORY_ID' => array('data_type' => 'integer'),
			'STAGE_SEMANTIC_ID' => array('data_type' => 'string'),
			'STAGE_ID' => array('data_type' => 'string'),
			'IS_LOST' => array('data_type' => 'boolean', 'values' => array('N', 'Y')),
			'CURRENCY_ID' => array('data_type' => 'string'),
			'INVOICE_SUM' => array('data_type' => 'float'),
			'INVOICE_QTY' => array('data_type' => 'integer'),
			'TOTAL_INVOICE_SUM' => array('data_type' => 'float'),
			'TOTAL_INVOICE_QTY' => array('data_type' => 'integer'),
			'TOTAL_SUM' => array('data_type' => 'float'),
			'TOTAL_OWED' => array('data_type' => 'float'),
			'UF_ATTR_1' => array('data_type' => 'integer')
		);
	}
	/**
	* @return void
	*/
	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$ownerID = isset($data['OWNER_ID']) ? (int)$data['OWNER_ID'] : 0;

		$dateField = new DatetimeField('D');
		$created = $sqlHelper->convertToDb(isset($data['CREATED_DATE']) ? $data['CREATED_DATE'] : null, $dateField);
		$start = $sqlHelper->convertToDb(isset($data['START_DATE']) ? $data['START_DATE'] : null, $dateField);
		$end = $sqlHelper->convertToDb(isset($data['END_DATE']) ? $data['END_DATE'] : null, $dateField);

		$year = isset($data['PERIOD_YEAR']) ? (int)$data['PERIOD_YEAR'] : 0;
		$quarter = isset($data['PERIOD_QUARTER']) ? (int)$data['PERIOD_QUARTER'] : 0;
		$month = isset($data['PERIOD_MONTH']) ? (int)$data['PERIOD_MONTH'] : 0;
		$day = isset($data['PERIOD_DAY']) ? (int)$data['PERIOD_DAY'] : 0;

		$categoryID = isset($data['CATEGORY_ID']) ? (int)$data['CATEGORY_ID'] : 0;
		$userID = isset($data['RESPONSIBLE_ID']) ? (int)$data['RESPONSIBLE_ID'] : 0;
		$isLost = isset($data['IS_LOST']) ? $sqlHelper->forSql($data['IS_LOST'], 1) : '';
		$semanticID = isset($data['STAGE_SEMANTIC_ID']) ? $sqlHelper->forSql($data['STAGE_SEMANTIC_ID'], 3) : '';
		$stageID = isset($data['STAGE_ID']) ? $sqlHelper->forSql($data['STAGE_ID'], 50) : '';
		$currencyID = isset($data['CURRENCY_ID']) ? $sqlHelper->forSql($data['CURRENCY_ID'], 3) : '';

		$invoiceSum = isset($data['INVOICE_SUM']) ? (double)$data['INVOICE_SUM'] : 0.0;
		$invoiceQty = isset($data['INVOICE_QTY']) ? (int)$data['INVOICE_QTY'] : 0;
		$totalInvoiceSum = isset($data['TOTAL_INVOICE_SUM']) ? (double)$data['TOTAL_INVOICE_SUM'] : 0.0;
		$totalInvoiceQty = isset($data['TOTAL_INVOICE_QTY']) ? (int)$data['TOTAL_INVOICE_QTY'] : 0;
		$totalSum = isset($data['TOTAL_SUM']) ? (double)$data['TOTAL_SUM'] : 0.0;
		$totalOwed = $totalSum - $totalInvoiceSum;

		$attr1 = isset($data['UF_ATTR_1']) ? (int)$data['UF_ATTR_1'] : 0;

		if($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$connection->queryExecute(
				"INSERT INTO b_crm_deal_inv_stat(
						OWNER_ID, CREATED_DATE, PERIOD_YEAR, PERIOD_QUARTER, PERIOD_MONTH, PERIOD_DAY,
						START_DATE, END_DATE, RESPONSIBLE_ID, CATEGORY_ID, STAGE_SEMANTIC_ID, STAGE_ID, IS_LOST,
						CURRENCY_ID, INVOICE_SUM, INVOICE_QTY, TOTAL_INVOICE_SUM, TOTAL_INVOICE_QTY,
						TOTAL_SUM, TOTAL_OWED,
						UF_ATTR_1)
					VALUES(
						{$ownerID}, {$created}, {$year}, {$quarter}, {$month}, {$day},
						{$start}, {$end}, {$userID}, {$categoryID}, '{$semanticID}', '{$stageID}', '{$isLost}',
						'{$currencyID}', {$invoiceSum}, {$invoiceQty}, {$totalInvoiceSum}, {$totalInvoiceQty},
						{$totalSum}, {$totalOwed},
						{$attr1})
					ON DUPLICATE KEY UPDATE
						START_DATE = {$start}, END_DATE = {$end}, RESPONSIBLE_ID = {$userID},
						CATEGORY_ID = {$categoryID}, STAGE_SEMANTIC_ID = '{$semanticID}', STAGE_ID = '{$stageID}', IS_LOST = '{$isLost}',
						CURRENCY_ID = '{$currencyID}', INVOICE_SUM = {$invoiceSum}, INVOICE_QTY = {$invoiceQty},
						TOTAL_INVOICE_SUM = {$totalInvoiceSum}, TOTAL_INVOICE_QTY = {$totalInvoiceQty},
						TOTAL_SUM = {$totalSum}, TOTAL_OWED = {$totalOwed},
						UF_ATTR_1 = {$attr1}"
			);
		}
		elseif($connection instanceof Main\DB\MssqlConnection)
		{
			$dbResult = $connection->query(
				"SELECT 'X' FROM b_crm_deal_inv_stat WHERE OWNER_ID = {$ownerID} AND CREATED_DATE = {$created}"
			);

			if(is_array($dbResult->fetch()))
			{
				$connection->queryExecute(
					"UPDATE b_crm_deal_inv_stat SET
						START_DATE = {$start}, END_DATE = {$end}, RESPONSIBLE_ID = {$userID},
						CATEGORY_ID = {$categoryID}, STAGE_SEMANTIC_ID = '{$semanticID}', STAGE_ID = '{$stageID}', IS_LOST = '{$isLost}',
						CURRENCY_ID = '{$currencyID}', INVOICE_SUM = {$invoiceSum}, INVOICE_QTY = {$invoiceQty},
						TOTAL_INVOICE_SUM = {$totalInvoiceSum}, TOTAL_INVOICE_QTY = {$totalInvoiceQty},
						TOTAL_SUM = {$totalSum}, TOTAL_OWED = {$totalOwed},
						UF_ATTR_1 = {$attr1}
					WHERE OWNER_ID = {$ownerID} AND CREATED_DATE = {$created}"
				);
			}
			else
			{
				$connection->queryExecute(
					"INSERT INTO b_crm_deal_inv_stat(
						OWNER_ID, CREATED_DATE, PERIOD_YEAR, PERIOD_QUARTER, PERIOD_MONTH, PERIOD_DAY,
						START_DATE, END_DATE, RESPONSIBLE_ID, CATEGORY_ID, STAGE_SEMANTIC_ID, STAGE_ID, IS_LOST,
						CURRENCY_ID, INVOICE_SUM, INVOICE_QTY, TOTAL_INVOICE_SUM, TOTAL_INVOICE_QTY,
						TOTAL_SUM, TOTAL_OWED,
						UF_ATTR_1)
					VALUES({$ownerID}, {$created}, {$year}, {$quarter}, {$month}, {$day},
							{$start}, {$end}, {$userID}, {$categoryID}, '{$semanticID}', '{$stageID}', '{$isLost}',
							'{$currencyID}', {$invoiceSum}, {$invoiceQty}, {$totalInvoiceSum}, {$totalInvoiceQty},
							{$totalSum}, {$totalOwed},
							{$attr1})"
				);
			}
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$connection->queryExecute("MERGE INTO b_crm_deal_inv_stat USING (SELECT {$ownerID} OWNER_ID, {$created} CREATED_DATE FROM dual)
				source ON
				(
					source.OWNER_ID = b_crm_deal_inv_stat.OWNER_ID
					AND source.CREATED_DATE = b_crm_deal_inv_stat.CREATED_DATE
				)
				WHEN MATCHED THEN
					UPDATE SET b_crm_deal_inv_stat.START_DATE = {$start},
						b_crm_deal_inv_stat.END_DATE = {$end},
						b_crm_deal_inv_stat.RESPONSIBLE_ID = {$userID},
						b_crm_deal_inv_stat.CATEGORY_ID = {$categoryID},
						b_crm_deal_inv_stat.STAGE_SEMANTIC_ID = '{$semanticID}',
						b_crm_deal_inv_stat.STAGE_ID = '{$stageID}',
						b_crm_deal_inv_stat.IS_LOST = '{$isLost}',
						b_crm_deal_inv_stat.CURRENCY_ID = '{$currencyID}',
						b_crm_deal_inv_stat.INVOICE_SUM = {$invoiceSum},
						b_crm_deal_inv_stat.INVOICE_QTY = {$invoiceQty},
						b_crm_deal_inv_stat.TOTAL_INVOICE_SUM = {$totalInvoiceSum},
						b_crm_deal_inv_stat.TOTAL_INVOICE_QTY = {$totalInvoiceQty},
						b_crm_deal_inv_stat.TOTAL_SUM = {$totalSum},
						b_crm_deal_inv_stat.TOTAL_OWED = {$totalOwed},
						b_crm_deal_inv_stat.UF_ATTR_1 = {$attr1}
						WHERE OWNER_ID = {$ownerID} AND CREATED_DATE = {$created}
				WHEN NOT MATCHED THEN
					INSERT (OWNER_ID, CREATED_DATE, PERIOD_YEAR, PERIOD_QUARTER, PERIOD_MONTH, PERIOD_DAY,
						START_DATE, END_DATE, RESPONSIBLE_ID, CATEGORY_ID, STAGE_SEMANTIC_ID, STAGE_ID, IS_LOST,
						CURRENCY_ID, INVOICE_SUM, INVOICE_QTY, TOTAL_INVOICE_SUM, TOTAL_INVOICE_QTY,
						TOTAL_SUM, TOTAL_OWED,
						UF_ATTR_1)
					VALUES({$ownerID}, {$created}, {$year}, {$quarter}, {$month}, {$day},
						{$start}, {$end}, {$userID}, {$categoryID}, '{$semanticID}', '{$stageID}', '{$isLost}',
						'{$currencyID}', {$invoiceSum}, {$invoiceQty}, {$totalInvoiceSum}, {$totalInvoiceQty},
						{$totalSum}, {$totalOwed},
						{$attr1})"
			);
		}
		else
		{
			$dbType = $connection->getType();
			throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
		}
	}
	/**
	* @return void
	*/
	public static function deleteByOwner($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		Main\Application::getConnection()->queryExecute("DELETE FROM b_crm_deal_inv_stat WHERE OWNER_ID = {$ownerID}");
	}
	/**
	* @return void
	*/
	public static function synchronize($ownerID, array $data)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$dateField = new DatetimeField('D');
		$start = $sqlHelper->convertToDb(isset($data['START_DATE']) ? $data['START_DATE'] : null, $dateField);
		$end = $sqlHelper->convertToDb(isset($data['END_DATE']) ? $data['END_DATE'] : null, $dateField);

		$userID = isset($data['RESPONSIBLE_ID']) ? (int)$data['RESPONSIBLE_ID'] : 0;

		$isLost = isset($data['IS_LOST']) ? $sqlHelper->forSql($data['IS_LOST'], 1) : '';
		$semanticID = isset($data['STAGE_SEMANTIC_ID']) ? $sqlHelper->forSql($data['STAGE_SEMANTIC_ID'], 3) : '';
		$stageID = isset($data['STAGE_ID']) ? $sqlHelper->forSql($data['STAGE_ID'], 50) : '';
		$totalSum = isset($data['TOTAL_SUM']) ? (double)$data['TOTAL_SUM'] : 0.0;

		$connection->queryExecute(
			"UPDATE b_crm_deal_inv_stat
				SET START_DATE = {$start}, END_DATE = {$end}, RESPONSIBLE_ID = {$userID},
				STAGE_SEMANTIC_ID = '{$semanticID}', STAGE_ID = '{$stageID}', IS_LOST = '{$isLost}',
				TOTAL_SUM = {$totalSum}, TOTAL_OWED = ({$totalSum} - TOTAL_INVOICE_SUM)
			WHERE OWNER_ID = {$ownerID}"
		);
	}
}