<?php
namespace Bitrix\Crm\Statistics\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Crm;

class LeadActivityStatisticsTable  extends Entity\DataManager
{
	/**
	* @return string
	*/
	public static function getTableName()
	{
		return 'b_crm_lead_act_stat';
	}
	/**
	* @return array
	*/
	public static function getMap()
	{
		return array(
			'OWNER_ID' => array('data_type' => 'integer', 'required' => true, 'primary' => true),
			'DEADLINE_DATE' => array('data_type' => 'date', 'required' => true, 'primary' => true),
			'DEADLINE_YEAR' => array('data_type' => 'integer'),
			'DEADLINE_QUARTER' => array('data_type' => 'integer'),
			'DEADLINE_MONTH' => array('data_type' => 'integer'),
			'DEADLINE_DAY' => array('data_type' => 'integer'),
			'CREATED_DATE' => array('data_type' => 'date'),
			'RESPONSIBLE_ID' => array('data_type' => 'integer'),
			'STATUS_SEMANTIC_ID' => array('data_type' => 'string'),
			'STATUS_ID' => array('data_type' => 'string'),
			'IS_JUNK' => array('data_type' => 'boolean', 'values' => array('N', 'Y')),
			'CALL_QTY' => array('data_type' => 'integer'),
			'MEETING_QTY' => array('data_type' => 'integer'),
			'EMAIL_QTY' => array('data_type' => 'integer'),
			'TOTAL' => array(
				'data_type' => 'integer',
				'expression' => array( '(%s + %s + %s)', 'CALL_QTY', 'MEETING_QTY', 'EMAIL_QTY' )
			),
			'UF_ATTR_1' => array('data_type' => 'integer')
		);
	}
	/**
	* @return void
	*/
	public static function upsert(array $data)
	{

		$fields = array(
			'CREATED_DATE' => isset($data['CREATED_DATE']) ? $data['CREATED_DATE'] : null,
			'RESPONSIBLE_ID' => isset($data['RESPONSIBLE_ID']) ? $data['RESPONSIBLE_ID'] : 0,
			'STATUS_SEMANTIC_ID' => isset($data['STATUS_SEMANTIC_ID']) ? $data['STATUS_SEMANTIC_ID'] : '',
			'STATUS_ID' => isset($data['STATUS_ID']) ? $data['STATUS_ID'] : '',
			'IS_JUNK' => isset($data['IS_JUNK']) ? $data['IS_JUNK'] : '',
			'CALL_QTY' => isset($data['CALL_QTY']) ? $data['CALL_QTY'] : 0,
			'MEETING_QTY' => isset($data['MEETING_QTY']) ? $data['MEETING_QTY'] : 0,
			'EMAIL_QTY' => isset($data['EMAIL_QTY']) ? $data['EMAIL_QTY'] : 0,
			'UF_ATTR_1' => isset($data['UF_ATTR_1']) ? $data['UF_ATTR_1'] : 0
		);

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_lead_act_stat',
			array('OWNER_ID', 'DEADLINE_DATE'),
			array_merge(
				$fields,
				array(
					'OWNER_ID' => isset($data['OWNER_ID']) ? $data['OWNER_ID'] : 0,
					'DEADLINE_DATE' => isset($data['DEADLINE_DATE']) ? $data['DEADLINE_DATE'] : null,
					'DEADLINE_YEAR' => isset($data['DEADLINE_YEAR']) ? $data['DEADLINE_YEAR'] : 0,
					'DEADLINE_QUARTER' => isset($data['DEADLINE_QUARTER']) ? $data['DEADLINE_QUARTER'] : 0,
					'DEADLINE_MONTH' => isset($data['DEADLINE_MONTH']) ? $data['DEADLINE_MONTH'] : 0,
					'DEADLINE_DAY' => isset($data['DEADLINE_DAY']) ? $data['DEADLINE_DAY'] : 0
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

		Main\Application::getConnection()->queryExecute("DELETE FROM b_crm_lead_act_stat WHERE OWNER_ID = {$ownerID}");
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
			"UPDATE b_crm_lead_act_stat SET RESPONSIBLE_ID = {$userID} WHERE OWNER_ID = {$ownerID}"
		);
	}
}