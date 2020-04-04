<?php
namespace Bitrix\Crm\Statistics\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\DatetimeField;

class LeadSumStatisticsTable  extends Entity\DataManager
{
	const MAX_SUM_SLOT_COUNT = 5;
	/**
	* @return string
	*/
	public static function getTableName()
	{
		return 'b_crm_lead_sum_stat';
	}
	/**
	* @return array
	*/
	public static function getMap()
	{
		return array(
			'OWNER_ID' => array('data_type' => 'integer', 'required' => true, 'primary' => true),
			'CREATED_DATE' => array('data_type' => 'date', 'required' => true, 'primary' => true),
			'PERIOD_YEAR' => array('data_type' => 'integer'),
			'PERIOD_QUARTER' => array('data_type' => 'integer'),
			'PERIOD_MONTH' => array('data_type' => 'integer'),
			'PERIOD_DAY' => array('data_type' => 'integer'),
			'RESPONSIBLE_ID' => array('data_type' => 'integer'),
			'STATUS_SEMANTIC_ID' => array('data_type' => 'string'),
			'STATUS_ID' => array('data_type' => 'string'),
			'SOURCE_ID' => array('data_type' => 'string'),
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
	* @return array Array of strings
	*/
	public static function getSumSlotFieldNames()
	{
		return array('UF_SUM_1', 'UF_SUM_2', 'UF_SUM_3', 'UF_SUM_4', 'UF_SUM_5');
	}
	/**
	* @return void
	*/
	public static function upsert(array $data)
	{
		$fields = array(
			'RESPONSIBLE_ID' => isset($data['RESPONSIBLE_ID']) ? $data['RESPONSIBLE_ID'] : 0,
			'STATUS_SEMANTIC_ID' => isset($data['STATUS_SEMANTIC_ID']) ? $data['STATUS_SEMANTIC_ID'] : '',
			'STATUS_ID' => isset($data['STATUS_ID']) ? $data['STATUS_ID'] : '',
			'SOURCE_ID' => isset($data['SOURCE_ID']) ? $data['SOURCE_ID'] : '',
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
			'b_crm_lead_sum_stat',
			array('OWNER_ID', 'CREATED_DATE'),
			array_merge(
				$fields,
				array(
					'OWNER_ID' => isset($data['OWNER_ID']) ? $data['OWNER_ID'] : 0,
					'CREATED_DATE' => isset($data['CREATED_DATE']) ? $data['CREATED_DATE'] : null,
					'PERIOD_YEAR' => isset($data['PERIOD_YEAR']) ? $data['PERIOD_YEAR'] : 0,
					'PERIOD_QUARTER' => isset($data['PERIOD_QUARTER']) ? $data['PERIOD_QUARTER'] : 0,
					'PERIOD_MONTH' => isset($data['PERIOD_MONTH']) ? $data['PERIOD_MONTH'] : 0,
					'PERIOD_DAY' => isset($data['PERIOD_DAY']) ? $data['PERIOD_DAY'] : 0
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

		Main\Application::getConnection()->queryExecute("DELETE FROM b_crm_lead_sum_stat WHERE OWNER_ID = {$ownerID}");
	}
	/**
	* @return void
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
				"DELETE FROM b_crm_lead_sum_stat WHERE OWNER_ID = {$ownerID} AND STATUS_SEMANTIC_ID IN('{$semantics}')");
		}
		else
		{
			Main\Application::getConnection()->queryExecute("DELETE FROM b_crm_lead_sum_stat WHERE OWNER_ID = {$ownerID}");
		}
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

		$userID = isset($data['RESPONSIBLE_ID']) ? (int)$data['RESPONSIBLE_ID'] : 0;
		Main\Application::getConnection()->queryExecute(
			"UPDATE b_crm_lead_sum_stat SET RESPONSIBLE_ID = {$userID} WHERE OWNER_ID = {$ownerID}"
		);
	}
	/**
	 * Synchronize sum fields
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
			"UPDATE b_crm_lead_sum_stat SET
				SUM_TOTAL = {$sumTotal}, UF_SUM_1 = {$sum1},
				UF_SUM_2 = {$sum2}, UF_SUM_3 = {$sum3},
				UF_SUM_4 = {$sum4}, UF_SUM_5 = {$sum5}
			    WHERE OWNER_ID = {$ownerID}"
		);
	}
}