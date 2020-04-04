<?php
namespace Bitrix\Crm\Statistics\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Crm;

class ContactActivityStreamStatisticsTable  extends Entity\DataManager
{
	/**
	* @return string
	*/
	public static function getTableName()
	{
		return 'b_crm_contact_act_stm_stat';
	}
	/**
	* @return array
	*/
	public static function getMap()
	{
		return array(
			'OWNER_ID' => array('data_type' => 'integer', 'required' => true, 'primary' => true),
			'DEADLINE_DATE' => array('data_type' => 'date', 'required' => true, 'primary' => true),
			'PROVIDER_ID' => array('data_type' => 'string', 'primary' => true),
			'PROVIDER_TYPE_ID' => array('data_type' => 'string', 'primary' => true),
			
			'RESPONSIBLE_ID' => array('data_type' => 'integer'),
			'INCOMING_QTY' => array('data_type' => 'integer'),
			'OUTGOING_QTY' => array('data_type' => 'integer'),
			'REVERSING_QTY' => array('data_type' => 'integer'),
			'MISSING_QTY' => array('data_type' => 'integer'),
			'TOTAL' => array(
				'data_type' => 'integer',
				'expression' => array('(%s + %s + %s + %s)', 'INCOMING_QTY', 'OUTGOING_QTY', 'REVERSING_QTY', 'MISSING_QTY')
			),
		);
	}
	/**
	* @return void
	*/
	public static function upsert(array $data)
	{
		$fields = array(
			'RESPONSIBLE_ID' => isset($data['RESPONSIBLE_ID']) ? (int)$data['RESPONSIBLE_ID'] : 0,
			'INCOMING_QTY' => isset($data['INCOMING_QTY']) ? (int)$data['INCOMING_QTY'] : 0,
			'OUTGOING_QTY' => isset($data['OUTGOING_QTY']) ? (int)$data['OUTGOING_QTY'] : 0,
			'REVERSING_QTY' => isset($data['REVERSING_QTY']) ? (int)$data['REVERSING_QTY'] : 0,
			'MISSING_QTY' => isset($data['MISSING_QTY']) ? (int)$data['MISSING_QTY'] : 0
		);

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_contact_act_stm_stat',
			array('OWNER_ID', 'DEADLINE_DATE', 'PROVIDER_ID', 'PROVIDER_TYPE_ID'),
			array_merge(
				$fields,
				array(
					'OWNER_ID' => isset($data['OWNER_ID']) ? $data['OWNER_ID'] : 0,
					'DEADLINE_DATE' => isset($data['DEADLINE_DATE']) ? $data['DEADLINE_DATE'] : null,
					'PROVIDER_ID' => isset($data['PROVIDER_ID']) ? $data['PROVIDER_ID'] : null,
					'PROVIDER_TYPE_ID' => isset($data['PROVIDER_TYPE_ID']) ? $data['PROVIDER_TYPE_ID'] : null
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

		Main\Application::getConnection()->queryExecute("DELETE FROM b_crm_contact_act_stm_stat WHERE OWNER_ID = {$ownerID}");
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
			"UPDATE b_crm_contact_act_stm_stat SET RESPONSIBLE_ID = {$userID} WHERE OWNER_ID = {$ownerID}"
		);
	}
}