<?php
namespace Bitrix\Timeman\Repository\Schedule;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftTable;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable;
use Bitrix\Timeman\Service\DependencyManager;

class ShiftPlanRepository
{
	public function findScheduleById($scheduleId)
	{
		return DependencyManager::getInstance()->getScheduleRepository()->findById($scheduleId);
	}

	public function findByScheduleId($scheduleId)
	{
		return ShiftPlanTable::query()
			->addSelect('*')
			->addSelect('SHIFT.*')
			->registerRuntimeField('SHIFT', (new Reference('SHIFT', ShiftTable::class, Join::on('this.SHIFT_ID', 'ref.ID')))->configureJoinType('INNER'))
			->where('SHIFT.SCHEDULE_ID', $scheduleId)
			->exec()
			->fetchAll();
	}

	public function findByScheduleShiftsUsersDates($scheduleId, $shiftIds, $userIds, $dates, $dateTo = null)
	{
		$userIds = is_array($userIds) ? $userIds : [$userIds];
		$shiftIds = is_array($shiftIds) ? $shiftIds : [$shiftIds];

		$res = ShiftPlanTable::query()
			->addSelect('*')
			->addSelect('SHIFT.*', 'SH_')
			->registerRuntimeField('SHIFT', (new Reference('SHIFT', ShiftTable::class, Join::on('this.SHIFT_ID', 'ref.ID')))->configureJoinType('INNER'))
			->whereIn('SHIFT.ID', array_merge([-1], $shiftIds))
			->whereIn('USER_ID', array_merge([-1], $userIds))
			->where('SHIFT.SCHEDULE_ID', $scheduleId);
		if ($dateTo === null)
		{
			if (is_array($dates))
			{
				$res->whereIn('DATE_ASSIGNED', $dates);
			}
			elseif ($dates instanceof \DateTime)
			{
				$res->where('DATE_ASSIGNED', Date::createFromPhp($dates));
			}
		}
		else
		{
			$res->whereBetween('DATE_ASSIGNED', Date::createFromPhp($dates), Date::createFromPhp($dateTo));
		}

		return $res->exec()
			->fetchAll();
	}

	/**
	 * @param $shiftPlan
	 * @return \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\Result|\Bitrix\Main\ORM\Data\UpdateResult
	 */
	public function save($shiftPlan)
	{
		/** @var ShiftPlan $shiftPlan */
		return $shiftPlan->save();
	}

	public function deleteByComplexId($shiftId, $userId, $dateAssigned)
	{
		return ShiftPlanTable::delete([
			'SHIFT_ID' => $shiftId,
			'USER_ID' => $userId,
			'DATE_ASSIGNED' => $dateAssigned,
		]);
	}

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
	 * @param $shiftPlan
	 * @return \Bitrix\Main\Result
	 */
	public function delete($shiftPlan)
	{
		if ($shiftPlan instanceof ShiftPlan)
		{
			return $shiftPlan->delete();
		}
		if (is_array($shiftPlan)
			&& isset($shiftPlan['DATE_ASSIGNED']) && $shiftPlan['DATE_ASSIGNED'] instanceof Date
			&& isset($shiftPlan['USER_ID']) && (int)$shiftPlan['USER_ID'] > 0
			&& isset($shiftPlan['SHIFT_ID']) && (int)$shiftPlan['SHIFT_ID'] > 0
		)
		{
			return ShiftPlanTable::delete([
				'SHIFT_ID' => (int)$shiftPlan['SHIFT_ID'],
				'USER_ID' => (int)$shiftPlan['USER_ID'],
				'DATE_ASSIGNED' => $shiftPlan['DATE_ASSIGNED'],
			]);
		}
		return (new Result())->addError(new Error('Internal error deleting shift plans'));
	}

	public function deleteByShiftIds($shiftIds)
	{
		$shiftIds = array_map('intval', array_filter($shiftIds));
		if (empty($shiftIds))
		{
			return;
		}
		Application::getConnection()->query("DELETE FROM b_timeman_work_shift_plan WHERE SHIFT_ID IN (" . implode(',', $shiftIds) . ")");
	}

	public function deleteByShiftAndNotUsersIds(array $shiftIds, array $activeUserIds)
	{
		$activeUserIds = array_map('intval', array_filter($activeUserIds));
		$shiftIds = array_map('intval', array_filter($shiftIds));
		if (empty($shiftIds) || empty($activeUserIds))
		{
			return;
		}
		Application::getConnection()->query("DELETE FROM b_timeman_work_shift_plan
					WHERE USER_ID NOT IN (" . implode(',', $activeUserIds) . ")
					AND SHIFT_ID IN (" . implode(',', $shiftIds) . ")");
	}
}