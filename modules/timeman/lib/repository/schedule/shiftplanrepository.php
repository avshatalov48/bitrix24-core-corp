<?php
namespace Bitrix\Timeman\Repository\Schedule;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftTable;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\EO_ShiftPlan_Query;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanCollection;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable;

class ShiftPlanRepository
{
	/**
	 * @param $shiftPlan
	 * @return \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\Result|\Bitrix\Main\ORM\Data\UpdateResult
	 */
	public function save($shiftPlan)
	{
		/** @var ShiftPlan $shiftPlan */
		return $shiftPlan->save();
	}

	/**
	 * @param $shiftId
	 * @param $userId
	 * @param Date $dateAssigned
	 * @return ShiftPlan|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findActiveByComplexId($shiftId, $userId, $dateAssigned)
	{
		if (!$dateAssigned || !($dateAssigned instanceof Date))
		{
			return null;
		}
		return $this->getActivePlansQuery()
			->addSelect('*')
			->where('SHIFT_ID', $shiftId)
			->where('USER_ID', $userId)
			->where('DATE_ASSIGNED', $dateAssigned)
			->exec()
			->fetchObject();
	}

	/**
	 * @param $shiftId
	 * @param $userId
	 * @param $dateAssigned
	 * @return ShiftPlan|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByComplexId($shiftId, $userId, $dateAssigned)
	{
		if (!$dateAssigned || !($dateAssigned instanceof Date))
		{
			return null;
		}
		return ShiftPlanTable::query()
			->addSelect('*')
			->where('SHIFT_ID', $shiftId)
			->where('USER_ID', $userId)
			->where('DATE_ASSIGNED', $dateAssigned)
			->exec()
			->fetchObject();
	}

	/**
	 * @return EO_ShiftPlan_Query
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getActivePlansQuery()
	{
		$query = ShiftPlanTable::query();
		$query->where('DELETED', ShiftPlanTable::DELETED_NO);
		return $query;
	}

	/**
	 * @param \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord $record
	 * @return ShiftPlan|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findActiveByRecord($record)
	{
		if ($record->getShiftId() <= 0)
		{
			return null;
		}
		$start = $record->buildRecordedStartDateTime();
		$start->setTimezone(new \DateTimeZone('UTC'));
		return $this->findActiveByComplexId(
			$record->getShiftId(),
			$record->getUserId(),
			new Date($start->format(ShiftPlanTable::DATE_FORMAT), ShiftPlanTable::DATE_FORMAT)
		);
	}

	/**
	 * @param $id
	 * @return ShiftPlan|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findActiveById($id, $select = [], $filter = null)
	{
		if (empty($select))
		{
			$select = ['ID'];
		}
		return $this->buildAllActiveQuery($select, $filter)
			->where('ID', $id)
			->exec()
			->fetchObject();
	}

	/**
	 * @param $select
	 * @param $filter
	 * @return EO_ShiftPlan_Query
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function buildAllActiveQuery($select, $filter)
	{
		$query = $this->getActivePlansQuery();

		foreach ($select as $field)
		{
			$query->addSelect($field);
		}
		if ($filter instanceof ConditionTree)
		{
			$query->where($filter);
		}
		return $query;
	}

	/**
	 * @param $id
	 * @return ShiftPlanCollection
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findAllActive($select = [], $filter = null)
	{
		return $this->buildAllActiveQuery($select, $filter)
			->exec()
			->fetchCollection();
	}

	public function updateAll($idList, $params)
	{
		return ShiftPlanTable::updateMulti($idList, $params);
	}

	public function findUserIdsByShiftIds($shiftIds)
	{
		if (empty($shiftIds))
		{
			return [];
		}
		$result = $this->buildAllActiveQuery(
			['D_USER_ID'],
			Query::filter()
				->whereIn('SHIFT_ID', $shiftIds)
		)
			->registerRuntimeField(new ExpressionField('D_USER_ID', 'DISTINCT USER_ID'))
			->exec()
			->fetchAll();
		if (empty($result))
		{
			return [];
		}
		return array_map('intval', array_column($result, 'D_USER_ID'));
	}

	public function findAllByUserDates($userId, Date $dateFrom, Date $dateTo, $shiftIdExcept = null): ShiftPlanCollection
	{
		$collectionQuery = $this->getActivePlansQuery()
			->addSelect('*')
			->addSelect('SHIFT')
			->addSelect('SCHEDULE.ID')
			->addSelect('SCHEDULE.NAME')
			->addSelect('SCHEDULE.SCHEDULE_TYPE')
			->registerRuntimeField(new Reference('SHIFT', ShiftTable::class, ['this.SHIFT_ID' => 'ref.ID']))
			->registerRuntimeField(new Reference('SCHEDULE', ScheduleTable::class, ['this.SHIFT.SCHEDULE_ID' => 'ref.ID']))
			->where('USER_ID', $userId)
			->whereBetween('DATE_ASSIGNED', $dateFrom, $dateTo);
		if ($shiftIdExcept !== null)
		{
			$collectionQuery->where('SHIFT_ID', '!=', $shiftIdExcept);
		}
		/*-*/// active shifts and schedules?
		return $collectionQuery->exec()->fetchCollection();
	}
}