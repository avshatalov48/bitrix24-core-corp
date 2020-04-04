<?php
namespace Bitrix\Crm\Statistics\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Crm;

class DealActivityStatisticsTable  extends Entity\DataManager
{
	/**
	* @return string
	*/
	public static function getTableName()
	{
		return 'b_crm_deal_act_stat';
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
			'RESPONSIBLE_ID' => array('data_type' => 'integer'),
			'CATEGORY_ID' => array('data_type' => 'integer'),
			'STAGE_SEMANTIC_ID' => array('data_type' => 'string'),
			'STAGE_ID' => array('data_type' => 'string'),
			'IS_LOST' => array('data_type' => 'boolean', 'values' => array('N', 'Y')),
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
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$ownerID = isset($data['OWNER_ID']) ? (int)$data['OWNER_ID'] : 0;

		$dateField = new DatetimeField('D');
		$deadline = $sqlHelper->convertToDb(isset($data['DEADLINE_DATE']) ? $data['DEADLINE_DATE'] : null, $dateField);

		$deadlineYear = isset($data['DEADLINE_YEAR']) ? (int)$data['DEADLINE_YEAR'] : 0;
		$deadlineQuarter = isset($data['DEADLINE_QUARTER']) ? (int)$data['DEADLINE_QUARTER'] : 0;
		$deadlineMonth = isset($data['DEADLINE_MONTH']) ? (int)$data['DEADLINE_MONTH'] : 0;
		$deadlineDay = isset($data['DEADLINE_DAY']) ? (int)$data['DEADLINE_DAY'] : 0;

		$categoryID = isset($data['CATEGORY_ID']) ? (int)$data['CATEGORY_ID'] : 0;
		$userID = isset($data['RESPONSIBLE_ID']) ? (int)$data['RESPONSIBLE_ID'] : 0;
		$isLost = isset($data['IS_LOST']) ? $sqlHelper->forSql($data['IS_LOST'], 1) : '';
		$semanticID = isset($data['STAGE_SEMANTIC_ID']) ? $sqlHelper->forSql($data['STAGE_SEMANTIC_ID'], 3) : '';
		$stageID = isset($data['STAGE_ID']) ? $sqlHelper->forSql($data['STAGE_ID'], 50) : '';

		$callQty = isset($data['CALL_QTY']) ? (int)$data['CALL_QTY'] : 0;
		$meetingQty = isset($data['MEETING_QTY']) ? (int)$data['MEETING_QTY'] : 0;
		$emailQty = isset($data['EMAIL_QTY']) ? (int)$data['EMAIL_QTY'] : 0;
		$attr1 = isset($data['UF_ATTR_1']) ? (int)$data['UF_ATTR_1'] : 0;

		if($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$connection->queryExecute(
				"INSERT INTO b_crm_deal_act_stat(
						OWNER_ID, DEADLINE_DATE, DEADLINE_YEAR, DEADLINE_QUARTER, DEADLINE_MONTH, DEADLINE_DAY,
						RESPONSIBLE_ID, CATEGORY_ID, STAGE_SEMANTIC_ID, STAGE_ID, IS_LOST,
						CALL_QTY, MEETING_QTY, EMAIL_QTY,
						UF_ATTR_1)
					VALUES(
						{$ownerID}, {$deadline}, {$deadlineYear}, {$deadlineQuarter}, {$deadlineMonth}, {$deadlineDay},
						{$userID}, {$categoryID}, '{$semanticID}', '{$stageID}', '{$isLost}',
						{$callQty}, {$meetingQty}, {$emailQty},
						{$attr1})
					ON DUPLICATE KEY UPDATE
						RESPONSIBLE_ID = {$userID},
						CATEGORY_ID = {$categoryID}, STAGE_SEMANTIC_ID = '{$semanticID}', STAGE_ID = '{$stageID}',  IS_LOST = '{$isLost}',
						CALL_QTY = {$callQty}, MEETING_QTY = {$meetingQty}, EMAIL_QTY = {$emailQty},
						UF_ATTR_1 = {$attr1}"
			);
		}
		elseif($connection instanceof Main\DB\MssqlConnection)
		{
			$dbResult = $connection->query(
				"SELECT 'X' FROM b_crm_deal_act_stat WHERE OWNER_ID = {$ownerID} AND DEADLINE_DATE = {$deadline}"
			);

			if(is_array($dbResult->fetch()))
			{
				$connection->queryExecute(
					"UPDATE b_crm_deal_act_stat SET
						RESPONSIBLE_ID = {$userID},
						CATEGORY_ID = {$categoryID}, STAGE_SEMANTIC_ID = '{$semanticID}', STAGE_ID = '{$stageID}', IS_LOST = '{$isLost}',
						CALL_QTY = {$callQty}, MEETING_QTY = {$meetingQty}, EMAIL_QTY = {$emailQty},
						UF_ATTR_1 = {$attr1}
					WHERE OWNER_ID = {$ownerID} AND DEADLINE_DATE = {$deadline}"
				);
			}
			else
			{
				$connection->queryExecute(
					"INSERT INTO b_crm_deal_act_stat(
						OWNER_ID, DEADLINE_DATE, DEADLINE_YEAR, DEADLINE_QUARTER, DEADLINE_MONTH, DEADLINE_DAY,
						RESPONSIBLE_ID, CATEGORY_ID, STAGE_SEMANTIC_ID, STAGE_ID, IS_LOST,
						CALL_QTY, MEETING_QTY, EMAIL_QTY,
						UF_ATTR_1)
					VALUES(
						{$ownerID}, {$deadline}, {$deadlineYear}, {$deadlineQuarter}, {$deadlineMonth}, {$deadlineDay},
						{$userID}, {$categoryID}, '{$semanticID}', '{$stageID}', '{$isLost}',
						{$callQty}, {$meetingQty}, {$emailQty},
						{$attr1})"
				);
			}
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$connection->queryExecute("MERGE INTO b_crm_deal_act_stat USING (SELECT {$ownerID} OWNER_ID, {$deadline} DEADLINE_DATE FROM dual)
				source ON
				(
					source.OWNER_ID = b_crm_deal_act_stat.OWNER_ID
					AND source.DEADLINE_DATE = b_crm_deal_act_stat.DEADLINE_DATE
				)
				WHEN MATCHED THEN
					UPDATE SET b_crm_deal_act_stat.RESPONSIBLE_ID = {$userID},
					b_crm_deal_act_stat.CATEGORY_ID = {$categoryID},
					b_crm_deal_act_stat.STAGE_SEMANTIC_ID = '{$semanticID}',
					b_crm_deal_act_stat.STAGE_ID = '{$stageID}',
					b_crm_deal_act_stat.IS_LOST = '{$isLost}',
					b_crm_deal_act_stat.CALL_QTY = {$callQty},
					b_crm_deal_act_stat.MEETING_QTY = {$meetingQty},
					b_crm_deal_act_stat.EMAIL_QTY = {$emailQty},
					b_crm_deal_act_stat.UF_ATTR_1 = {$attr1}
					WHERE OWNER_ID = {$ownerID} AND DEADLINE_DATE = {$deadline}
				WHEN NOT MATCHED THEN
					INSERT (
						OWNER_ID, DEADLINE_DATE, DEADLINE_YEAR, DEADLINE_QUARTER, DEADLINE_MONTH, DEADLINE_DAY,
						RESPONSIBLE_ID, CATEGORY_ID, STAGE_SEMANTIC_ID, STAGE_ID, IS_LOST,
						CALL_QTY, MEETING_QTY, EMAIL_QTY,
						UF_ATTR_1)
					VALUES(
						{$ownerID}, {$deadline}, {$deadlineYear}, {$deadlineQuarter}, {$deadlineMonth}, {$deadlineDay},
						{$userID}, {$categoryID}, '{$semanticID}', '{$stageID}', '{$isLost}',
						{$callQty}, {$meetingQty}, {$emailQty},
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

		Main\Application::getConnection()->queryExecute("DELETE FROM b_crm_deal_act_stat WHERE OWNER_ID = {$ownerID}");
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
			"UPDATE b_crm_deal_act_stat SET RESPONSIBLE_ID = {$userID} WHERE OWNER_ID = {$ownerID}"
		);
	}
}