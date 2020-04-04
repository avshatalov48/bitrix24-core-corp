<?php
namespace Bitrix\Crm\Statistics\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Crm;

class LeadConversionStatisticsTable  extends Entity\DataManager
{
	/**
	* @return string
	*/
	public static function getTableName()
	{
		return 'b_crm_lead_conv_stat';
	}
	/**
	* @return array
	*/
	public static function getMap()
	{
		return array(
			'OWNER_ID' => array('data_type' => 'integer', 'required' => true, 'primary' => true),
			'ENTRY_DATE' => array('data_type' => 'date', 'required' => true, 'primary' => true),
			'CREATED_DATE' => array('data_type' => 'date'),
			'RESPONSIBLE_ID' => array('data_type' => 'integer', 'required' => true),
			'CONTACT_QTY' => array('data_type' => 'integer'),
			'COMPANY_QTY' => array('data_type' => 'integer'),
			'DEAL_QTY' => array('data_type' => 'integer'),
			'TOTALS_DATE' => array('data_type' => 'date'),
			'TOTALS' => array(
				'data_type' => 'LeadSumStatistics',
				'reference' => array(
					'=this.OWNER_ID' => 'ref.OWNER_ID',
					'=this.TOTALS_DATE' => 'ref.CREATED_DATE'
				)
			)
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
			'CONTACT_QTY' => isset($data['CONTACT_QTY']) ? $data['CONTACT_QTY'] : 0,
			'COMPANY_QTY' => isset($data['COMPANY_QTY']) ? $data['COMPANY_QTY'] : 0,
			'DEAL_QTY' => isset($data['DEAL_QTY']) ? $data['DEAL_QTY'] : 0,
			'TOTALS_DATE' => isset($data['TOTALS_DATE']) ? $data['TOTALS_DATE'] : null,
		);

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_lead_conv_stat',
			array('OWNER_ID', 'ENTRY_DATE'),
			array_merge(
				$fields,
				array(
					'OWNER_ID' => isset($data['OWNER_ID']) ? $data['OWNER_ID'] : 0,
					'ENTRY_DATE' => isset($data['ENTRY_DATE']) ? $data['ENTRY_DATE'] : null
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

		Main\Application::getConnection()->queryExecute("DELETE FROM b_crm_lead_conv_stat WHERE OWNER_ID = {$ownerID}");
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
			"UPDATE b_crm_lead_conv_stat SET RESPONSIBLE_ID = {$userID} WHERE OWNER_ID = {$ownerID}"
		);
	}
}