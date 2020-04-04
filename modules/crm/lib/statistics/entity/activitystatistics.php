<?php
namespace Bitrix\Crm\Statistics\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Crm;

class ActivityStatisticsTable  extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_act_stat';
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
			'OWNER_TYPE_ID' => array('data_type' => 'integer'),
			'RESPONSIBLE_ID' => array('data_type' => 'integer'),
			'COMPLETED' => array('data_type' => 'boolean', 'values' => array('N', 'Y')),
			'STATUS_ID' => array('data_type' => 'integer'),
			'MARK_ID' => array('data_type' => 'integer'),
			'SOURCE_ID' => array('data_type' => 'string'),
			'STREAM_ID' => array('data_type' => 'integer'),
			'CURRENCY_ID' => array('data_type' => 'string'),
			'SUM_TOTAL' => array('data_type' => 'float'),
		);
	}

	/**
	 * @param array $data
	 */
	public static function upsert(array $data)
	{
		$fields = array(
			'OWNER_TYPE_ID' => isset($data['OWNER_TYPE_ID']) ? (int)$data['OWNER_TYPE_ID'] : 0,
			'RESPONSIBLE_ID' => isset($data['RESPONSIBLE_ID']) ? (int)$data['RESPONSIBLE_ID'] : 0,
			'COMPLETED' => isset($data['COMPLETED']) && $data['COMPLETED'] === 'Y' ? 'Y' : 'N',
			'STATUS_ID' => isset($data['STATUS_ID']) ? (int)$data['STATUS_ID'] : 0,
			'MARK_ID' => isset($data['MARK_ID']) ? (int)$data['MARK_ID'] : 0,
			'SOURCE_ID' => isset($data['SOURCE_ID']) ? $data['SOURCE_ID'] : '',
			'STREAM_ID' => isset($data['STREAM_ID']) ? (int)$data['STREAM_ID'] : 0,
			'CURRENCY_ID' => isset($data['CURRENCY_ID']) ? $data['CURRENCY_ID'] : '',
			'SUM_TOTAL' => isset($data['SUM_TOTAL']) ? (float)$data['SUM_TOTAL'] : 0.0
		);

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_act_stat',
			array('OWNER_ID', 'DEADLINE_DATE', 'PROVIDER_ID', 'PROVIDER_TYPE_ID'),
			array_merge(
				$fields,
				array(
					'OWNER_ID' => isset($data['OWNER_ID']) ? $data['OWNER_ID'] : 0,
					'DEADLINE_DATE' => isset($data['DEADLINE_DATE']) ? $data['DEADLINE_DATE'] : null,
					'PROVIDER_ID' => isset($data['PROVIDER_ID']) ? $data['PROVIDER_ID'] : null,
					'PROVIDER_TYPE_ID' => isset($data['PROVIDER_TYPE_ID']) ? $data['PROVIDER_TYPE_ID'] : null,
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
	 * @param $ownerID
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

		Main\Application::getConnection()->queryExecute("DELETE FROM b_crm_act_stat WHERE OWNER_ID = {$ownerID}");
	}

	/**
	 * @param int $ownerID
	 * @param array $data
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
		Main\Application::getConnection()->queryExecute(
			"UPDATE b_crm_act_stat SET RESPONSIBLE_ID = {$userID} WHERE OWNER_ID = {$ownerID}"
		);
	}
}