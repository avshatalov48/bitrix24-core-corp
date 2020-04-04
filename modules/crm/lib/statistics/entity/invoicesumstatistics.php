<?php
namespace Bitrix\Crm\Statistics\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;

class InvoiceSumStatisticsTable  extends Entity\DataManager
{
	const MAX_SUM_SLOT_COUNT = 5;

	/**
	 * Get database table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_invoice_sum_stat';
	}
	/**
	 * Get entity map.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'OWNER_ID' => array('data_type' => 'integer', 'required' => true, 'primary' => true),
			'CREATED_DATE' => array('data_type' => 'date', 'required' => true, 'primary' => true),
			'BILL_DATE' => array('data_type' => 'date', 'required' => true),
			'PAY_BEFORE_DATE' => array('data_type' => 'date', 'required' => true),
			'PAID_DATE' => array('data_type' => 'date'),
			'IS_PAID_INTIME' => array('data_type' => 'boolean', 'values' => array('N', 'Y')),
			'CLOSED_DATE' => array('data_type' => 'date'),
			//'PERIOD_YEAR' => array('data_type' => 'integer'),
			//'PERIOD_QUARTER' => array('data_type' => 'integer'),
			//'PERIOD_MONTH' => array('data_type' => 'integer'),
			//'PERIOD_DAY' => array('data_type' => 'integer'),
			'RESPONSIBLE_ID' => array('data_type' => 'integer'),
			'COMPANY_ID' => array('data_type' => 'integer'),
			'CONTACT_ID' => array('data_type' => 'integer'),
			'STATUS_SEMANTIC_ID' => array('data_type' => 'string'),
			'STATUS_ID' => array('data_type' => 'string'),
			'IS_JUNK' => array('data_type' => 'boolean', 'values' => array('N', 'Y')),
			'CURRENCY_ID' => array('data_type' => 'string'),
			'SUM_TOTAL' => array('data_type' => 'float'),
			'UF_SUM_1' => array('data_type' => 'float'),
			'UF_SUM_2' => array('data_type' => 'float'),
			'UF_SUM_3' => array('data_type' => 'float'),
			'UF_SUM_4' => array('data_type' => 'float'),
			'UF_SUM_5' => array('data_type' => 'float'),
			'UF_ATTR_1' => array('data_type' => 'integer')
		);
	}
	/**
	 * Get sum slot fields names
	 * @return array Array of strings
	 */
	public static function getSumSlotFieldNames()
	{
		return array('UF_SUM_1', 'UF_SUM_2', 'UF_SUM_3', 'UF_SUM_4', 'UF_SUM_5');
	}
	/**
	 * Execute upsert operation.
	 * @param array $data Entity fields data.
	 * @return void
	 */
	public static function upsert(array $data)
	{
		$fields = array(
			'BILL_DATE' => isset($data['BILL_DATE']) ? $data['BILL_DATE'] : null,
			'PAY_BEFORE_DATE' => isset($data['PAY_BEFORE_DATE']) ? $data['PAY_BEFORE_DATE'] : null,
			'PAID_DATE' => isset($data['PAID_DATE']) ? $data['PAID_DATE'] : null,
			'IS_PAID_INTIME' => isset($data['IS_PAID_INTIME']) ? $data['IS_PAID_INTIME'] : '',
			'CLOSED_DATE' => isset($data['CLOSED_DATE']) ? $data['CLOSED_DATE'] : null,
			'RESPONSIBLE_ID' => isset($data['RESPONSIBLE_ID']) ? $data['RESPONSIBLE_ID'] : 0,
			'COMPANY_ID' => isset($data['COMPANY_ID']) ? $data['COMPANY_ID'] : 0,
			'CONTACT_ID' => isset($data['CONTACT_ID']) ? $data['CONTACT_ID'] : 0,
			'STATUS_SEMANTIC_ID' => isset($data['STATUS_SEMANTIC_ID']) ? $data['STATUS_SEMANTIC_ID'] : '',
			'STATUS_ID' => isset($data['STATUS_ID']) ? $data['STATUS_ID'] : '',
			'IS_JUNK' => isset($data['IS_JUNK']) ? $data['IS_JUNK'] : '',
			'CURRENCY_ID' => isset($data['CURRENCY_ID']) ? $data['CURRENCY_ID'] : '',
			'SUM_TOTAL' => isset($data['SUM_TOTAL']) ? $data['SUM_TOTAL'] : 0.0,
			'UF_SUM_1' => isset($data['UF_SUM_1']) ? $data['UF_SUM_1'] : 0.0,
			'UF_SUM_2' => isset($data['UF_SUM_2']) ? $data['UF_SUM_2'] : 0.0,
			'UF_SUM_3' => isset($data['UF_SUM_3']) ? $data['UF_SUM_3'] : 0.0,
			'UF_SUM_4' => isset($data['UF_SUM_4']) ? $data['UF_SUM_4'] : 0.0,
			'UF_SUM_5' => isset($data['UF_SUM_5']) ? $data['UF_SUM_5'] : 0.0,
			'UF_ATTR_1' => isset($data['UF_ATTR_1']) ? $data['UF_ATTR_1'] : 0
		);

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_invoice_sum_stat',
			array('OWNER_ID', 'CREATED_DATE'),
			array_merge(
				$fields,
				array(
					'OWNER_ID' => isset($data['OWNER_ID']) ? $data['OWNER_ID'] : 0,
					'CREATED_DATE' => isset($data['CREATED_DATE']) ? $data['CREATED_DATE'] : null,
					//'PERIOD_YEAR' => isset($data['PERIOD_YEAR']) ? $data['PERIOD_YEAR'] : 0,
					//'PERIOD_QUARTER' => isset($data['PERIOD_QUARTER']) ? $data['PERIOD_QUARTER'] : 0,
					//'PERIOD_MONTH' => isset($data['PERIOD_MONTH']) ? $data['PERIOD_MONTH'] : 0,
					//'PERIOD_DAY' => isset($data['PERIOD_DAY']) ? $data['PERIOD_DAY'] : 0
				)
			),
			$fields
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
	/**
	 * Delete records by owner.
	 * @param int $ownerID Owner ID.
	 * @return void
	 * @throws Main\ArgumentException
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

		Main\Application::getConnection()->queryExecute("DELETE FROM b_crm_invoice_sum_stat WHERE OWNER_ID = {$ownerID}");
	}
	/**
	 * Delete records by filter.
	 * @param array $filter Filter parameters.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function deleteByFilter(array $filter)
	{
		$ownerID = isset($filter['OWNER_ID']) ? (int)$filter['OWNER_ID'] : 0;
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$semanticID = isset($filter['SEMANTIC_ID']) ? $filter['SEMANTIC_ID'] : array();
		if(!is_array($semanticID))
		{
			$semanticID = $semanticID !== '' ? array($semanticID) : array();
		}

		if(!empty($semantics))
		{
			$semantics = implode("','", $semanticID);
			Main\Application::getConnection()->queryExecute(
				"DELETE FROM b_crm_invoice_sum_stat WHERE OWNER_ID = {$ownerID} AND STATUS_SEMANTIC_ID IN('{$semantics}')");
		}
		else
		{
			Main\Application::getConnection()->queryExecute("DELETE FROM b_crm_invoice_sum_stat WHERE OWNER_ID = {$ownerID}");
		}
	}

	/**
	 * Synchonize records belonged to owner specified.
	 * @param int $ownerID Owner ID.
	 * @param array $data Entity fields data.
	 * @return void
	 * @throws Main\ArgumentException
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

		$userID = isset($data['RESPONSIBLE_ID']) ? (int)$data['RESPONSIBLE_ID'] : 0;
		$companyID = isset($data['COMPANY_ID']) ? (int)$data['COMPANY_ID'] : 0;
		$contactID = isset($data['CONTACT_ID']) ? (int)$data['CONTACT_ID'] : 0;
		$billDate = isset($data['BILL_DATE']) ? \CCrmDateTimeHelper::DateToSql($data['BILL_DATE']) : 'NULL';
		$payBeforeDate = isset($data['PAY_BEFORE_DATE']) ? \CCrmDateTimeHelper::DateToSql($data['PAY_BEFORE_DATE']) : 'NULL';
		$paidDate = isset($data['PAID_DATE']) ? \CCrmDateTimeHelper::DateToSql($data['PAID_DATE']) : 'NULL';
		$isPaidIntime = isset($data['IS_PAID_INTIME']) && $data['IS_PAID_INTIME'] === 'Y' ? 'Y' : 'N';
		$closedDate = isset($data['CLOSED_DATE']) ? \CCrmDateTimeHelper::DateToSql($data['CLOSED_DATE']) : 'NULL';

		Main\Application::getConnection()->queryExecute(
			"UPDATE b_crm_invoice_sum_stat
				SET RESPONSIBLE_ID = {$userID},
					COMPANY_ID = {$companyID},
					CONTACT_ID = {$contactID},
					BILL_DATE = {$billDate},
					PAY_BEFORE_DATE = {$payBeforeDate},
					PAID_DATE = {$paidDate},
					IS_PAID_INTIME = '{$isPaidIntime}',
					CLOSED_DATE = {$closedDate}
			WHERE OWNER_ID = {$ownerID}"
		);
	}
	/**
	 * Synchronize record's sum columns belonged to owner specified.
	 * @param int $ownerID Owner ID.
	 * @param array $data Source data.
	 * @return void
	 */
	public static function synchronizeSumFields($ownerID, array $data)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$sumTotal = isset($data['SUM_TOTAL']) ? (double)$data['SUM_TOTAL'] : 0.0;
		$sum1 = isset($data['UF_SUM_1']) ? (double)$data['UF_SUM_1'] : 0.0;
		$sum2 = isset($data['UF_SUM_2']) ? (double)$data['UF_SUM_2'] : 0.0;
		$sum3 = isset($data['UF_SUM_3']) ? (double)$data['UF_SUM_3'] : 0.0;
		$sum4 = isset($data['UF_SUM_4']) ? (double)$data['UF_SUM_4'] : 0.0;
		$sum5 = isset($data['UF_SUM_5']) ? (double)$data['UF_SUM_5'] : 0.0;

		Main\Application::getConnection()->queryExecute(
			"UPDATE b_crm_invoice_sum_stat SET 
				SUM_TOTAL = {$sumTotal}, UF_SUM_1 = {$sum1},
				UF_SUM_2 = {$sum2}, UF_SUM_3 = {$sum3},
				UF_SUM_4 = {$sum4}, UF_SUM_5 = {$sum5}
			WHERE OWNER_ID = {$ownerID}"
		);
	}
}