<?php
namespace Bitrix\Crm\Statistics\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Type\Date;

/**
 * Class DealInvoiceStatisticsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DealInvoiceStatistics_Query query()
 * @method static EO_DealInvoiceStatistics_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DealInvoiceStatistics_Result getById($id)
 * @method static EO_DealInvoiceStatistics_Result getList(array $parameters = [])
 * @method static EO_DealInvoiceStatistics_Entity getEntity()
 * @method static \Bitrix\Crm\Statistics\Entity\EO_DealInvoiceStatistics createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Statistics\Entity\EO_DealInvoiceStatistics_Collection createCollection()
 * @method static \Bitrix\Crm\Statistics\Entity\EO_DealInvoiceStatistics wakeUpObject($row)
 * @method static \Bitrix\Crm\Statistics\Entity\EO_DealInvoiceStatistics_Collection wakeUpCollection($rows)
 */
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

		$ownerID = (int)($data['OWNER_ID'] ?? 0);

		$created = $data['CREATED_DATE'] ?? null;
		$start = $data['START_DATE'] ?? null;
		$end = $data['END_DATE'] ?? null;

		$year = (int)($data['PERIOD_YEAR'] ?? 0);
		$quarter = (int)($data['PERIOD_QUARTER'] ?? 0);
		$month = (int)($data['PERIOD_MONTH'] ?? 0);
		$day = (int)($data['PERIOD_DAY'] ?? 0);

		$categoryID = (int)($data['CATEGORY_ID'] ?? 0);
		$userID = (int)($data['RESPONSIBLE_ID'] ?? 0);
		$isLost = mb_substr($data['IS_LOST'] ?? '', 0, 1);
		$semanticID = mb_substr($data['STAGE_SEMANTIC_ID'] ?? '', 0, 3);
		$stageID = mb_substr($data['STAGE_ID'] ?? '', 0, 50);
		$currencyID = mb_substr($data['CURRENCY_ID'] ?? '', 0, 3);

		$invoiceSum = (double)($data['INVOICE_SUM'] ?? 0.0);
		$invoiceQty = (int)($data['INVOICE_QTY'] ?? 0);
		$totalInvoiceSum = (double)($data['TOTAL_INVOICE_SUM'] ?? 0.0);
		$totalInvoiceQty = (int)($data['TOTAL_INVOICE_QTY'] ?? 0);
		$totalSum = (double)($data['TOTAL_SUM'] ?? 0.0);
		$totalOwed = $totalSum - $totalInvoiceSum;

		$attr1 = (int)($data['UF_ATTR_1'] ?? 0);

		$sql = $sqlHelper->prepareMerge(
			'b_crm_deal_inv_stat',
			[
				'OWNER_ID',
				'CREATED_DATE',
			],
			[
				'OWNER_ID' => $ownerID,
				'CREATED_DATE' => $created,
				'PERIOD_YEAR' => $year,
				'PERIOD_QUARTER' => $quarter,
				'PERIOD_MONTH' => $month,
				'PERIOD_DAY' => $day,
				'START_DATE' => $start,
				'END_DATE' => $end,
				'RESPONSIBLE_ID' => $userID,
				'CATEGORY_ID' => $categoryID,
				'STAGE_SEMANTIC_ID' => $semanticID,
				'STAGE_ID' => $stageID,
				'IS_LOST' => $isLost,
				'CURRENCY_ID' => $currencyID,
				'INVOICE_SUM' => $invoiceSum,
				'INVOICE_QTY' => $invoiceQty,
				'TOTAL_INVOICE_SUM' => $totalInvoiceSum,
				'TOTAL_INVOICE_QTY' => $totalInvoiceQty,
				'TOTAL_SUM' => $totalSum,
				'TOTAL_OWED' => $totalOwed,
				'UF_ATTR_1' => $attr1,
			],
			[
				'START_DATE' => $start,
				'END_DATE' => $end,
				'RESPONSIBLE_ID' => $userID,
				'CATEGORY_ID' => $categoryID,
				'STAGE_SEMANTIC_ID' => $semanticID,
				'STAGE_ID' => $stageID,
				'IS_LOST' => $isLost,
				'CURRENCY_ID' => $currencyID,
				'INVOICE_SUM' => $invoiceSum,
				'INVOICE_QTY' => $invoiceQty,
				'TOTAL_INVOICE_SUM' => $totalInvoiceSum,
				'TOTAL_INVOICE_QTY' => $totalInvoiceQty,
				'TOTAL_SUM' => $totalSum,
				'TOTAL_OWED' => $totalOwed,
				'UF_ATTR_1' => $attr1,
			]
		);
		$connection->queryExecute($sql[0]);
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