<?php
namespace Bitrix\Timeman\Repository\Worktime;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Timeman\Helper\TimeDictionary;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftTable;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordCollection;
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
	 * @param $recordId
	 * @return WorktimeRecord|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findById($recordId)
	{
		if (!($recordId > 0))
		{
			return null;
		}
		return WorktimeRecordTable::query()
			->addSelect('*')
			->where('ID', $recordId)
			->exec()
			->fetchObject();
	}

	/**
	 * @param $userId
	 * @param $shiftId
	 * @param $scheduleId
	 * @param array $select
	 * @param null $filter
	 * @return WorktimeRecord|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByUserShiftSchedule($userId, $shiftId, $scheduleId, $select, $filter = null)
	{
		$query = WorktimeRecordTable::query();
		foreach ($select as $field)
		{
			$query->addSelect($field);
		}
		if ($filter instanceof ConditionTree)
		{
			$query->where($filter);
		}
		return $query->where('USER_ID', $userId)
			->where('SHIFT_ID', $shiftId)
			->where('SCHEDULE_ID', $scheduleId)
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
		if (in_array('SHIFT', $withEntities, true))
		{
			$res->addSelect('SHIFT')
				->registerRuntimeField((new Reference('SHIFT',
					ShiftTable::class,
					Join::on('this.SHIFT_ID', 'ref.ID')))->configureJoinType('LEFT')
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
		if (in_array('USER', $withEntities, true))
		{
			$res->addSelect('USER.ID')
				->addSelect('USER.NAME')
				->addSelect('USER.LAST_NAME')
				->addSelect('USER.SECOND_NAME')
				->addSelect('USER.PERSONAL_PHOTO')
				->addSelect('USER.TIME_ZONE')
				->addSelect('USER.LOGIN')
				->addSelect('USER.WORK_POSITION')
				->addSelect('USER.EMAIL');
		}
		$record = $res->exec()->fetchObject();
		if (!$record)
		{
			return $record;
		}
		if (in_array('REPORTS', $withEntities, true))
		{
			$reports = WorktimeReportTable::query()
				->addSelect('*')
				->where('ENTRY_ID', $record->getId())
				->addOrder('ID', 'DESC')
				->exec()
				->fetchCollection();
			if ($reports->count() > 0)
			{
				$record->defineReports($reports);
			}
		}
		if (in_array('WORKTIME_EVENTS', $withEntities, true))
		{
			$subQuery = WorktimeEventTable::query()
				->addSelect('MAX_ACTUAL_TIMESTAMP')
				->registerRuntimeField(new ExpressionField('MAX_ACTUAL_TIMESTAMP', 'MAX(ACTUAL_TIMESTAMP)'))
				->registerRuntimeField(
					new ExpressionField(
						'EVENT_TYPE_ALIAS',
						"CASE 
						WHEN EVENT_TYPE = 'START_WITH_ANOTHER_TIME' THEN 'EDIT_START'
						WHEN EVENT_TYPE = 'STOP_WITH_ANOTHER_TIME' THEN 'EDIT_STOP'
						ELSE EVENT_TYPE END"
					)
				)
				->where('WORKTIME_RECORD_ID', $recordId)
				->addGroup('EVENT_TYPE_ALIAS');

			$query = WorktimeEventTable::query()
				->addSelect('*')
				->where('WORKTIME_RECORD_ID', $record->getId())
				->where(Query::filter()->logic('or')
					->whereIn('ACTUAL_TIMESTAMP', $subQuery)
					->where('ACTUAL_TIMESTAMP', null)
				)
			;

			$events = $query->exec()->fetchCollection();
			if ($events->count() > 0)
			{
				$record->defineWorktimeEvents($events);
			}

		}
		return $record;
	}

	public function findLatestRecord($userId): ?WorktimeRecord
	{
		return WorktimeRecordTable::query()
			->addSelect('*')
			->addSelect('SCHEDULE')
			->addSelect('SCHEDULE.SCHEDULE_VIOLATION_RULES')
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
			->setCacheTtl(3600 * 12)
			->cacheJoins(true)
			->exec()
			->fetchObject();
	}

	public function findOverlappingRecordByDates(WorktimeRecord $record): bool
	{
		$userId = $record->getUserId();
		$startTimestamp = $record->getRecordedStartTimestamp();
		$stopTimestamp = $record->getRecordedStopTimestamp();
		$id = $record->getId();
		$scheduleId = $record->getScheduleId();
		$shiftId = $record->getShiftId();

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
		if ($scheduleId > 0)
		{
			$query->where('SCHEDULE_ID', $scheduleId);
		}
		if ($shiftId > 0)
		{
			$query->where('SHIFT_ID', $shiftId);
		}

		$query->where($orFilter);
		return $query->fetch() !== false;
	}

	public function findAllForPeriod(\DateTime $fromDateTime, \DateTime $toDateTime, Schedule $schedule, $userIds)
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

	/**
	 * @param $selectFields
	 * @param ConditionTree $whereConditions
	 * @return WorktimeRecordCollection
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findAll($selectFields, ConditionTree $whereConditions)
	{
		$query = WorktimeRecordTable::query();
		foreach ($selectFields as $selectField)
		{
			if (in_array($selectField, ['SHIFT', 'SCHEDULE'], true))
			{
				continue;
			}
			$query->addSelect($selectField);
		}
		$query->where($whereConditions);
		$records = $query->exec()->fetchCollection();
		if ($records->count() === 0)
		{
			return $records;
		}
		if (in_array('SCHEDULE', $selectFields, true))
		{
			$scheduleIds = array_filter($records->getScheduleIdList(), function ($id) {
				return $id > 0;
			});
			if (!empty($scheduleIds))
			{
				$schedules = ScheduleTable::query()
					->addSelect('*')
					->whereIn('ID', $scheduleIds)
					->exec()
					->fetchCollection();
				foreach ($records as $record)
				{
					if ($record->getScheduleId() > 0 && $schedule = $schedules->getByPrimary($record->getScheduleId()))
					{
						$record->defineSchedule($schedule);
					}
				}
			}
		}
		if (in_array('SHIFT', $selectFields, true))
		{
			$shiftIds = array_filter($records->getShiftIdList(), function ($id) {
				return $id > 0;
			});
			if (!empty($shiftIds))
			{
				$shifts = ShiftTable::query()
					->addSelect('*')
					->whereIn('ID', $shiftIds)
					->exec()
					->fetchCollection();
				foreach ($records as $record)
				{
					if ($record->getShiftId() > 0 && $shift = $shifts->getByPrimary($record->getShiftId()))
					{
						$record->defineShift($shift);
					}
				}
			}
		}
		return $records;
	}

	/**
	 * @param WorktimeRecordCollection $records
	 * @param $fieldsData
	 * @return \Bitrix\Main\ORM\Data\UpdateResult|Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function saveAll($records, $fieldsData)
	{
		if (!empty($records->getIdList()))
		{
			return WorktimeRecordTable::updateMulti($records->getIdList(), $fieldsData, true);
		}
		return new Result();
	}

	public function buildOpenRecordsQuery(Schedule $schedule, ShiftCollection $shiftCollection)
	{
		$filter = Query::filter()
			->where('SCHEDULE_ID', $schedule->getId())
			->where('RECORDED_STOP_TIMESTAMP', 0)
			->where('RECORDED_START_TIMESTAMP', '>', TimeHelper::getInstance()->getUtcNowTimestamp() - (4 * TimeDictionary::SECONDS_PER_DAY));
		if ($shiftCollection->count() > 0)
		{
			$filter->whereIn('SHIFT_ID', $shiftCollection->getIdList());
		}
		return $filter;
	}
}