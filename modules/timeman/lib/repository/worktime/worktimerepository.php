<?php
namespace Bitrix\Timeman\Repository\Worktime;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftTable;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable;
use Bitrix\Timeman\Model\Worktime\Report\WorktimeReportTable;

class WorktimeRepository
{
	/**
	 * @param WorktimeEvent|WorktimeRecord $entity
	 * @return Result
	 */
	public function save($entity)
	{
		return $entity->save();
	}

	public function findRecordOwnerUserId($recordId)
	{
		$result = WorktimeRecordTable::query()->addSelect('USER_ID')->where('ID', $recordId)->fetch();
		if ($result === false)
		{
			return null;
		}
		return $result['USER_ID'];
	}

	/**
	 * @param Shift $shift
	 * @param \DateTime $dateTime
	 */
	public function findRecordsByShift($shift, $userId, $utcTimestamp)
	{
		$res = $this->buildRecordByShiftQuery($shift, $userId, $utcTimestamp)
			->exec()
			->fetchCollection();

		return $res;
	}

	public function findRecordByShift($shift, $userId, $utcTimestamp)
	{
		$res = $this->buildRecordByShiftQuery($shift, $userId, $utcTimestamp)
			->exec()
			->fetchObject();

		return $res;
	}

	public function findRecordsByScheduleShiftUser($scheduleIds, $shiftIds, $userIds)
	{
		$scheduleIds = !is_array($scheduleIds) ? [$scheduleIds] : $scheduleIds;
		$shiftIds = !is_array($shiftIds) ? [$shiftIds] : $shiftIds;
		$userIds = !is_array($userIds) ? [$userIds] : $userIds;

		$subQuery = WorktimeRecordTable::query()
			->addSelect('MAX_ID')
			->registerRuntimeField(new ExpressionField('MAX_ID', 'MAX(ID)'))
			->whereIn('SCHEDULE_ID', array_merge([-1], $scheduleIds))
			->whereIn('SHIFT_ID', array_merge([0], $shiftIds))
			->whereIn('USER_ID', array_merge([-1], $userIds))
			->addGroup('SCHEDULE_ID')
			->addGroup('SHIFT_ID')
			->addGroup('USER_ID');

		return WorktimeRecordTable::query()
			->addSelect('*')
			->whereIn('ID', $subQuery)
			->exec()
			->fetchCollection();
	}

	/**
	 * @param Shift $shift
	 * @param $userId
	 * @param $utcTimestamp
	 * @return \Bitrix\Timeman\Model\Worktime\Record\EO_WorktimeRecord_Query
	 */
	private function buildRecordByShiftQuery($shift, $userId, $utcTimestamp)
	{
		return WorktimeRecordTable::query()
			->addSelect('*')
			->addSelect('SCHEDULE')
			->addSelect('SHIFT')
			->registerRuntimeField((new Reference('SCHEDULE',
				ScheduleTable::class,
				Join::on('this.SCHEDULE_ID', 'ref.ID')))->configureJoinType('INNER')
			)
			->registerRuntimeField((new Reference('SHIFT',
				ShiftTable::class,
				Join::on('this.SHIFT_ID', 'ref.ID')))->configureJoinType('INNER')
			)
			->addSelect('SCHEDULE.SHIFTS')
			->where('USER_ID', $userId)
			->where('SCHEDULE_ID', $shift->getScheduleId())
			->where('SHIFT_ID', $shift->getId())
			->where('RECORDED_START_TIMESTAMP', '>=', $utcTimestamp);
	}

	public function findRecordById($recordId)
	{
		return WorktimeRecordTable::query()
			->addSelect('*')
			->where('ID', $recordId)
			->exec()
			->fetchObject();
	}

	public function findRecordByUserShiftDate($userId, $shiftId, $dateFormatted)
	{
		return WorktimeRecordTable::query()
			->addSelect('*')
			->registerRuntimeField(new ExpressionField('DATE_STRING', 'FROM_UNIXTIME(%s, "%%Y-%%m-%%d")', ['RECORDED_START_TIMESTAMP',]))
			->where('USER_ID', $userId)
			->where('SHIFT_ID', $shiftId)
			->where('DATE_STRING', $dateFormatted)
			->exec()
			->fetchObject();
	}

	/**
	 * @param $recordId
	 * @param array $withEntities
	 * @return WorktimeRecord
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByIdWith($recordId, $withEntities = [])
	{
		$res = WorktimeRecordTable::query()
			->addSelect('*')
			->where('ID', $recordId);
		if (in_array('SCHEDULE', $withEntities, true))
		{
			$res->addSelect('SCHEDULE')
				->registerRuntimeField((new Reference('SCHEDULE',
					ScheduleTable::class,
					Join::on('this.SCHEDULE_ID', 'ref.ID')))->configureJoinType('LEFT')
				);
		}
		if (in_array('SCHEDULE.SHIFTS', $withEntities, true))
		{
			$res->addSelect('SCHEDULE.SHIFTS');
		}
		if (in_array('SCHEDULE.SCHEDULE_VIOLATION_RULES', $withEntities, true))
		{
			$res->addSelect('SCHEDULE.SCHEDULE_VIOLATION_RULES');
		}
		if (in_array('REPORTS', $withEntities, true))
		{
			$res
				->registerRuntimeField((new OneToMany('REPORTS', WorktimeReportTable::class, 'RECORD'))->configureJoinType('LEFT'))
				->addSelect('REPORTS')
				->addOrder('REPORTS.ID', 'DESC');
		}
		if (in_array('SHIFT', $withEntities, true))
		{
			$res->addSelect('SHIFT')
				->registerRuntimeField((new Reference('SHIFT',
					ShiftTable::class,
					Join::on('this.SHIFT_ID', 'ref.ID')))->configureJoinType('LEFT')
				);
		}
		if (in_array('USER', $withEntities, true))
		{
			$res->addSelect('USER.ID')
				->addSelect('USER.NAME')
				->addSelect('USER.LAST_NAME')
				->addSelect('USER.SECOND_NAME')
				->addSelect('USER.PERSONAL_PHOTO')
				->addSelect('USER.LOGIN')
				->addSelect('USER.WORK_POSITION')
				->addSelect('USER.EMAIL');
		}
		if (in_array('WORKTIME_EVENTS', $withEntities, true))
		{
			$subQuery = WorktimeEventTable::query()
				->addSelect('MAX_ACTUAL_TIMESTAMP')
				->registerRuntimeField(new ExpressionField('MAX_ACTUAL_TIMESTAMP', 'MAX(ACTUAL_TIMESTAMP)'))
				->registerRuntimeField(new ExpressionField('EVENT_TYPE_ALIAS', 'CASE 
					WHEN EVENT_TYPE = "START_WITH_ANOTHER_TIME" THEN "EDIT_START"
					WHEN EVENT_TYPE = "STOP_WITH_ANOTHER_TIME" THEN "EDIT_STOP"
					ELSE EVENT_TYPE END')
				)
				->where('WORKTIME_RECORD_ID', $recordId)
				->addGroup('EVENT_TYPE_ALIAS');
			$res->addSelect('WORKTIME_EVENTS')
				->where(Query::filter()->logic('or')
					->whereIn('WORKTIME_EVENTS.ACTUAL_TIMESTAMP', $subQuery)
					->where('WORKTIME_EVENTS.ACTUAL_TIMESTAMP', null)
				);
		}
		return $res->exec()
			->fetchObject();
	}

	public function findByIdWithUser($recordId)
	{
		return $this->findByIdWith($recordId, ['USER']);
	}

	public function findLatestRecord($userId)
	{
		return WorktimeRecordTable::query()
			->addSelect('*')
			->addSelect('SCHEDULE')
			->addSelect('SHIFT')
			->registerRuntimeField((new Reference('SCHEDULE',
				ScheduleTable::class,
				Join::on('this.SCHEDULE_ID', 'ref.ID')))->configureJoinType('LEFT')
			)
			->registerRuntimeField((new Reference('SHIFT',
				ShiftTable::class,
				Join::on('this.SHIFT_ID', 'ref.ID')))->configureJoinType('LEFT')
			)
			->where('USER_ID', $userId)
			->addOrder('ID', 'DESC')
			->setLimit(1)
			->exec()
			->fetchObject();
	}

	public function findIntersectingRecordByDates($userId, $startTimestamp, $stopTimestamp, $id = 0)
	{
		$orFilter = Query::filter()->logic('or');
		$orFilter->where(
			Query::filter()->logic('and')
				->where('RECORDED_START_TIMESTAMP', '<=', $startTimestamp)
				->where('RECORDED_STOP_TIMESTAMP', '>=', $startTimestamp)
		);
		if ($stopTimestamp > 0)
		{
			$orFilter->where(
				Query::filter()->logic('and')
					->where('RECORDED_START_TIMESTAMP', '<=', $stopTimestamp)
					->where('RECORDED_START_TIMESTAMP', '>=', $startTimestamp)
			);
		}
		$query = WorktimeRecordTable::query()
			->addSelect('ID')
			->where('USER_ID', $userId);
		if ($id > 0)
		{
			$query->where('ID', '!=', $id);
		}

		$query->where($orFilter);
		return $query->fetch() !== false;
	}

	public function findRecordsForPeriod(\DateTime $fromDateTime, \DateTime $toDateTime, Schedule $schedule, $userIds)
	{
		return WorktimeRecordTable::query()
			->registerRuntimeField(new ExpressionField('DURATION', 'SUM(RECORDED_DURATION)'))
			->addSelect('USER_ID')
			->addSelect('DURATION')
			->where('RECORDED_START_TIMESTAMP', '>', $fromDateTime->getTimestamp())
			->where('RECORDED_START_TIMESTAMP', '<', $toDateTime->getTimestamp())
			->whereIn('USER_ID', $userIds)
			->where('SCHEDULE_ID', $schedule->getId())
			->addGroup('USER_ID')
			->exec()
			->fetchAll();
	}
}