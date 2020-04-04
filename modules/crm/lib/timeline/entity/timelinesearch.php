<?php
namespace Bitrix\Crm\Timeline\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;

class TimelineSearchTable  extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_timeline_search';
	}
	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'OWNER_ID' => array('data_type' => 'integer', 'primary' => true),
			'SEARCH_CONTENT' => array('data_type' => 'string')
		);
	}
	public static function upsert(array $data)
	{
		$ownerID = isset($data['OWNER_ID']) ? (int)$data['OWNER_ID'] : 0;
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Must contains "OWNER_ID" field', 'data');
		}

		$fields = array('SEARCH_CONTENT' => isset($data['SEARCH_CONTENT']) ? $data['SEARCH_CONTENT'] : '');

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_timeline_search',
			array('OWNER_ID'),
			array_merge(
				$fields,
				array('OWNER_ID' => $ownerID)
			),
			$fields
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
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

		Main\Application::getConnection()->queryExecute("DELETE FROM b_crm_timeline_search WHERE OWNER_ID = {$ownerID}");
	}
}