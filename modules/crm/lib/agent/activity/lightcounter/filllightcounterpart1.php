<?php

namespace Bitrix\Crm\Agent\Activity\LightCounter;

use Bitrix\Crm\Activity\LightCounter\CalculateParams;
use Bitrix\Crm\Activity\LightCounter\CounterLightTime;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Crm\Model\ActivityPingOffsetsTable;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Config\Option;

/**
 * This is the first part of the agent to populate the LIGHT_TIME fields for counters.
 * Filling in the b_crm_act_counter_light table and
 * filling in the LIGHT_COUNTER_AT field in b_crm_entity_countable_act
 */
final class FillLightCounterPart1
{
	public const LAST_ID_OPTION_NAME = 'LIGHT_COUNTER_AGENT_PART_1_LAST_ID';
	public const PART_IS_DONE_NAME = 'LIGHT_COUNTER_AGENT_PART_1_IS_DONE';

	private const BATCH_SIZE = 100;

	private const PART_IS_DONE = false;

	private const PART_HAS_MORE_ITERATIONS = true;

	/**
	 * @return bool return TRUE when there is more data to process
	 */
	public function execute(): bool
	{
		if (Option::get('crm', self::PART_IS_DONE_NAME, 'N') === 'Y')
		{
			return self::PART_IS_DONE;
		}

		$lastId = $this->getLastId();
		$activities = $this->queryUncompletedActivitiesToProcess($lastId);

		if (empty($activities))
		{
			Option::set('crm', self::PART_IS_DONE_NAME, 'Y');
			return self::PART_IS_DONE;
		}

		foreach ($activities as $row)
		{
			$offsets = $row['MAX_OFFSET'] ? [$row['MAX_OFFSET']] : null;
			$lightTime = (new CounterLightTime)->calculate(
				CalculateParams::createFromArrays($row, $offsets)
			);

			$this->insertIntoToLightTimeTable($row['ID'], $lightTime);
			$this->updateCountableActTable($row['ID'], $lightTime, $row['DEADLINE']);
		}

		$this->setLastId(end($activities)['ID']);

		return self::PART_HAS_MORE_ITERATIONS;
	}


	private function insertIntoToLightTimeTable(int $activityId, DateTime $lightTime)
	{
		$isNotified = $lightTime->getTimestamp() < (new DateTime())->getTimestamp() ? 'Y' : 'N';
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$row = [
			'ACTIVITY_ID' => $activityId,
			'LIGHT_COUNTER_AT' => $sqlHelper->convertToDbDateTime($lightTime),
			'IS_LIGHT_COUNTER_NOTIFIED' => $sqlHelper->convertToDbString($isNotified),
		];
		$rowStr = implode(',', $row);
		$sql = <<<SQL
INSERT INTO b_crm_act_counter_light (ACTIVITY_ID, LIGHT_COUNTER_AT, IS_LIGHT_COUNTER_NOTIFIED)
VALUES ($rowStr)  
ON DUPLICATE KEY UPDATE LIGHT_COUNTER_AT = VALUES(LIGHT_COUNTER_AT), 
	IS_LIGHT_COUNTER_NOTIFIED = VALUES(IS_LIGHT_COUNTER_NOTIFIED);
SQL;
		Application::getConnection()->query($sql);
	}

	private function updateCountableActTable(int $activityId, DateTime $lightTime, ?DateTime $deadline)
	{
		$deadline = $deadline ?? \CCrmDateTimeHelper::getMaxDatabaseDateObject();
		$dt = clone $deadline;
		$dt->setTime(23, 59, 59);

		$sqlHelper = Application::getConnection()->getSqlHelper();
		$date = $sqlHelper->convertToDbDateTime($lightTime);
		$deadlineExpired = $sqlHelper->convertToDbDateTime($dt);
		EntityCountableActivityTable::cleanCache();

		$sql = <<<SQL
update b_crm_entity_countable_act 
set LIGHT_COUNTER_AT = {$date}, DEADLINE_EXPIRED_AT = {$deadlineExpired}  
where ACTIVITY_ID = {$activityId};
SQL;
		Application::getConnection()->query($sql);
	}

	private function queryUncompletedActivitiesToProcess(int $lastId): array
	{
		$activitiesIds = ActivityTable::query()
			->setSelect(['ID'])
			->where('ID', '>', $lastId)
			->where('COMPLETED', '=', 'N')
			->setOrder(['ID' => 'ASC'])
			->setLimit(self::BATCH_SIZE)
			->fetchAll();

		if (empty($activitiesIds))
		{
			return [];
		}

		$activitiesIds = array_column($activitiesIds, 'ID');

		$activities = ActivityTable::query()
			->setSelect(['ID', 'NOTIFY_TYPE', 'NOTIFY_VALUE', 'DEADLINE'])
			->whereIn('ID', $activitiesIds)
			->fetchAll();

		$offsets = ActivityPingOffsetsTable::query()
			->addSelect('ACTIVITY_ID')
			->addSelect('MAX_OFFSET')
			->registerRuntimeField('', new ExpressionField('MAX_OFFSET', 'MAX(OFFSET)'))
			->whereIn('ACTIVITY_ID', $activitiesIds)
			->setGroup('ACTIVITY_ID')
			->fetchAll();

		$offsetKeyVal = [];
		foreach ($offsets as $offset)
		{
			$offsetKeyVal[$offset['ACTIVITY_ID']] = $offset['MAX_OFFSET'];
		}

		foreach ($activities as &$activity)
		{
			$activity['MAX_OFFSET'] = $offsetKeyVal[$activity['ID']] ?? null;
		}

		return $activities;
	}

	private function getLastId(): int
	{
		return (int)Option::get('crm', self::LAST_ID_OPTION_NAME, -1);
	}

	private function setLastId(int $lastId)
	{
		Option::set('crm', self::LAST_ID_OPTION_NAME, $lastId);
	}

	public static function cleanOptions()
	{
		Option::delete('crm', ['name' => self::LAST_ID_OPTION_NAME]);
		Option::delete('crm', ['name' => self::PART_IS_DONE_NAME]);
	}

}