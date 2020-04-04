<?php
namespace Bitrix\Timeman\Repository\Schedule;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftTable;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable;
use Bitrix\Timeman\Service\DependencyManager;

class ShiftRepository
{
	/**
	 * @param $id
	 * @return Shift|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findById($id)
	{
		if (!($id > 0))
		{
			return null;
		}
		return $this->getActiveShiftsQuery()->addSelect('*')->where('ID', $id)->exec()->fetchObject();
	}

	/**
	 * @param Shift $shift
	 * @return \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult
	 */
	public function save(Shift $shift)
	{
		return $shift->save();
	}

	/**
	 * @param $shiftId
	 * @return Shift|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByIdWithSchedule($shiftId)
	{
		return $this->getActiveShiftsQuery()
			->addSelect('*')
			->addSelect('SCHEDULE')
			->addSelect('SCHEDULE.SCHEDULE_VIOLATION_RULES')
			->where('ID', $shiftId)
			->where('SCHEDULE.DELETED', ScheduleTable::DELETED_NO)
			->exec()
			->fetchObject();
	}

	public function findByIdAndScheduleId($shiftId, $scheduleId)
	{
		return $this->getActiveShiftsQuery()
			->addSelect('*')
			->addSelect('SCHEDULE')
			->where('SCHEDULE.DELETED', ScheduleTable::DELETED_NO)
			->where('ID', $shiftId)
			->where('SCHEDULE.ID', $scheduleId)
			->exec()
			->fetchObject();
	}

	public function findScheduleById($scheduleId)
	{
		return DependencyManager::getInstance()->getScheduleRepository()->findById($scheduleId);
	}

	public function delete($id)
	{
		$res = $this->deleteShiftPlans([$id]);
		if (!$res->isSuccess())
		{
			return $res;
		}
		return ShiftTable::delete($id);
	}

	public function deleteShiftPlans($shiftIds, $userIds = [])
	{
		$shiftIds = array_unique(array_map('intval', $shiftIds));
		$userIds = array_unique(array_map('intval', $userIds));
		$connection = Application::getConnection();
		if (!empty($userIds))
		{
			$sql = 'DELETE FROM ' . ShiftPlanTable::getTableName() . ' WHERE ';
			foreach ($userIds as $userId)
			{
				$conditions[] = " (SHIFT_ID in (" . implode(',', $shiftIds) . ') AND USER_ID = ' . (int)$userId . ')';
			}
			$sql .= implode(' OR ', $conditions);
			$connection->query($sql);
		}
		else
		{
			$connection->query('DELETE FROM ' . ShiftPlanTable::getTableName() . " WHERE SHIFT_ID in (" . implode(',', $shiftIds) . ')');
		}
		if ($connection->getAffectedRowsCount() >= 0)
		{
			return new Result();
		}
		return (new Result())->addError(new Error('Internal error while deleting shift plans'));
	}

	public function markShiftDeleted($shiftId)
	{
		return $this->save(Shift::wakeUp(['ID' => $shiftId])->setDeleted(true));
	}

	/**
	 * @return \Bitrix\Timeman\Model\Schedule\Shift\EO_Shift_Query
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getActiveShiftsQuery()
	{
		return ShiftTable::query()->where('DELETED', ShiftTable::DELETED_NO);
	}

	public function findScheduleIdByShiftId($shiftId)
	{
		$result = $this->getActiveShiftsQuery()->addSelect('SCHEDULE_ID')->where('ID', $shiftId)->fetch();
		if ($result === false)
		{
			return null;
		}
		return $result['SCHEDULE_ID'];
	}

	/**
	 * @param $scheduleId
	 * @return ShiftCollection
	 */
	public function findShiftsBySchedule($scheduleId)
	{
		return $this->getActiveShiftsQuery()
			->addSelect('*')
			->where('SCHEDULE_ID', $scheduleId)
			->exec()
			->fetchCollection();
	}
}