<?php
namespace Bitrix\Crm\History\Entity;
use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Crm\History\HistoryEntryType;

class DealStageHistoryTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_deal_stage_history';
	}
	public static function getMap()
	{
		return array(
			'ID' => array('data_type' => 'integer', 'primary' => true, 'autocomplete' => true),
			'TYPE_ID' => array('data_type' => 'integer', 'required' => true),
			'OWNER_ID' => array('data_type' => 'integer', 'required' => true),
			'CREATED_TIME' => array('data_type' => 'datetime', 'required' => true),
			'CREATED_DATE' => array('data_type' => 'date'),
			'EFFECTIVE_DATE' => array('data_type' => 'date'),
			'START_DATE' => array('data_type' => 'date', 'required' => true),
			'END_DATE' => array('data_type' => 'date', 'required' => true),
			'PERIOD_YEAR' => array('data_type' => 'integer'),
			'PERIOD_QUARTER' => array('data_type' => 'integer'),
			'PERIOD_MONTH' => array('data_type' => 'integer'),
			'START_PERIOD_YEAR' => array('data_type' => 'integer'),
			'START_PERIOD_QUARTER' => array('data_type' => 'integer'),
			'START_PERIOD_MONTH' => array('data_type' => 'integer'),
			'END_PERIOD_YEAR' => array('data_type' => 'integer'),
			'END_PERIOD_QUARTER' => array('data_type' => 'integer'),
			'END_PERIOD_MONTH' => array('data_type' => 'integer'),
			'RESPONSIBLE_ID' => array('data_type' => 'integer'),
			'CATEGORY_ID' => array('data_type' => 'integer'),
			'STAGE_SEMANTIC_ID' => array('data_type' => 'string'),
			'STAGE_ID' => array('data_type' => 'string'),
			'IS_LOST' => array('data_type' => 'boolean', 'values' => array('N', 'Y')),
		);
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

		Main\Application::getConnection()->queryExecute("DELETE FROM b_crm_deal_stage_history WHERE OWNER_ID = {$ownerID}");
	}
	public static function deleteByFilter(array $filter,  $borderID = 0)
	{
		$ownerID = isset($filter['OWNER_ID']) ? (int)$filter['OWNER_ID'] : 0;
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException("Filter parameter 'OWNER_ID' must be greater than zero.", 'filter');
		}

		$filter = array(
			"OWNER_ID = {$ownerID}"
		);

		if(isset($filter['TYPE_ID']) && $filter['TYPE_ID'] != HistoryEntryType::UNDEFINED)
		{
			$typeID = (int)$filter['TYPE_ID'];
			if(!HistoryEntryType::isDefined($typeID))
			{
				throw new Main\ArgumentException("Filter parameter 'TYPE_ID' value is not supported in current context.", 'filter');
			}

			$filter[] = "TYPE_ID = {$typeID}";
		}

		if(isset($filter['STAGE_ID']) && $filter['STAGE_ID'] != '')
		{
			$stageID = $filter['STAGE_ID'];
			$filter[] = "STAGE_ID = '{$stageID}'";
		}

		if($borderID > 0)
		{
			if(!is_int($borderID))
			{
				$borderID = (int)$borderID;
			}
			$filter[] = "ID < {$borderID}";
		}

		Main\Application::getConnection()->queryExecute("DELETE from b_crm_deal_stage_history WHERE ".implode(' AND ', $filter));
	}
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

		Main\Application::getConnection()->queryExecute(
			"UPDATE b_crm_deal_stage_history
				SET START_DATE = {$start}, END_DATE = {$end}, RESPONSIBLE_ID = {$userID} WHERE OWNER_ID = {$ownerID}"
		);

		//region Synchronize effective date
		$creation = HistoryEntryType::CREATION;
		$finalization = HistoryEntryType::FINALIZATION;
		Main\Application::getConnection()->queryExecute(
			"UPDATE b_crm_deal_stage_history
				SET EFFECTIVE_DATE =
					CASE
						WHEN TYPE_ID = {$creation} THEN START_DATE
						WHEN TYPE_ID = {$finalization} THEN END_DATE
						ELSE CREATED_DATE END
				WHERE OWNER_ID = {$ownerID} AND TYPE_ID IN({$creation}, {$finalization})"
		);
		//endregion
	}
}