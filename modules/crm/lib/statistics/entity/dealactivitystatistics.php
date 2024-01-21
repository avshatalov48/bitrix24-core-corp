<?php
namespace Bitrix\Crm\Statistics\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Crm;

/**
 * Class DealActivityStatisticsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DealActivityStatistics_Query query()
 * @method static EO_DealActivityStatistics_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DealActivityStatistics_Result getById($id)
 * @method static EO_DealActivityStatistics_Result getList(array $parameters = [])
 * @method static EO_DealActivityStatistics_Entity getEntity()
 * @method static \Bitrix\Crm\Statistics\Entity\EO_DealActivityStatistics createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Statistics\Entity\EO_DealActivityStatistics_Collection createCollection()
 * @method static \Bitrix\Crm\Statistics\Entity\EO_DealActivityStatistics wakeUpObject($row)
 * @method static \Bitrix\Crm\Statistics\Entity\EO_DealActivityStatistics_Collection wakeUpCollection($rows)
 */
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

		$ownerID = (int)($data['OWNER_ID'] ?? 0);

		$deadline = $data['DEADLINE_DATE'] ?? null;

		$deadlineYear = (int)($data['DEADLINE_YEAR'] ?? 0);
		$deadlineQuarter = (int)($data['DEADLINE_QUARTER'] ?? 0);
		$deadlineMonth = (int)($data['DEADLINE_MONTH'] ?? 0);
		$deadlineDay = (int)($data['DEADLINE_DAY'] ?? 0);

		$categoryID = (int)($data['CATEGORY_ID'] ?? 0);
		$userID = (int)($data['RESPONSIBLE_ID'] ?? 0);
		$isLost = mb_substr($data['IS_LOST'] ?? '', 0, 1);
		$semanticID = mb_substr($data['STAGE_SEMANTIC_ID'] ?? '', 0, 3);
		$stageID = mb_substr($data['STAGE_ID'] ?? '', 0, 50);

		$callQty = (int)($data['CALL_QTY'] ?? 0);
		$meetingQty = (int)($data['MEETING_QTY'] ?? 0);
		$emailQty = (int)($data['EMAIL_QTY'] ?? 0);
		$attr1 = (int)($data['UF_ATTR_1'] ?? 0);

		$sql = $sqlHelper->prepareMerge(
			'b_crm_deal_act_stat',
			[
				'OWNER_ID',
				'DEADLINE_DATE',
			],
			[
				'OWNER_ID' => $ownerID,
				'DEADLINE_DATE' => $deadline,
				'DEADLINE_YEAR' => $deadlineYear,
				'DEADLINE_QUARTER' => $deadlineQuarter,
				'DEADLINE_MONTH' => $deadlineMonth,
				'DEADLINE_DAY' => $deadlineDay,
				'RESPONSIBLE_ID' => $userID,
				'CATEGORY_ID' => $categoryID,
				'STAGE_SEMANTIC_ID' => $semanticID,
				'STAGE_ID' => $stageID,
				'IS_LOST' => $isLost,
				'CALL_QTY' => $callQty,
				'MEETING_QTY' => $meetingQty,
				'EMAIL_QTY' => $emailQty,
				'UF_ATTR_1' => $attr1,
			],
			[
				'RESPONSIBLE_ID' => $userID,
				'CATEGORY_ID' => $categoryID,
				'STAGE_SEMANTIC_ID' => $semanticID,
				'STAGE_ID' => $stageID,
				'IS_LOST' => $isLost,
				'CALL_QTY' => $callQty,
				'MEETING_QTY' => $meetingQty,
				'EMAIL_QTY' => $emailQty,
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