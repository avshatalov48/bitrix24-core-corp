<?php
namespace Bitrix\Timeman\Service\Worktime\Action;

use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleCollection;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Provider\Schedule\ShiftPlanProvider;

class ShiftsManager
{
	/** @var ScheduleCollection */
	private $activeSchedules;
	/** @var ShiftPlanProvider */
	private $shiftPlanProvider;
	private $userId;

	public function __construct($userId, ScheduleCollection $activeSchedules, ShiftPlanProvider $shiftPlanProvider)
	{
		$this->userId = $userId;
		$this->activeSchedules = $activeSchedules;
		$this->shiftPlanProvider = $shiftPlanProvider;
	}

	private function buildShiftWithDate(\DateTime $userDateTime, $checkRelevant, ?ShiftWithDate $previousShitWithDate = null): ?ShiftWithDate
	{
		if (!$this->activeSchedules->count() === 0 || $this->activeSchedules->hasFlextime() ||
			$this->activeSchedules->obtainActiveShifts()->count() === 0)
		{
			return null;
		}
		/** @var ShiftWithDate[] $nextShiftsWithDates */
		$nextShiftsWithDates = [];
		foreach ($this->activeSchedules->getAll() as $schedule)
		{
			$daysToCheck = $checkRelevant ? $this->getSearchDaysForRelevantShift($schedule) : $this->getSearchDaysForNextShift($schedule);
			$nextShiftsWithDates = array_merge($nextShiftsWithDates, $this->calculateShiftsWithDate($schedule, $userDateTime, $checkRelevant, $daysToCheck, $previousShitWithDate));
		}
		$nextShiftsWithDates = array_filter($nextShiftsWithDates);

		if (count($nextShiftsWithDates) <= 1)
		{
			return empty($nextShiftsWithDates) ? null : reset($nextShiftsWithDates);
		}
		else
		{
			$shiftedShifts = [];
			$fixedShifts = [];
			foreach ($nextShiftsWithDates as $shiftWithDate)
			{
				if ($shiftWithDate->getSchedule()->isShifted())
				{
					$shiftedShifts[$shiftWithDate->getDateTimeStart()->getTimestamp()] = $shiftWithDate;
				}
				else
				{
					$fixedShifts[$shiftWithDate->getDateTimeStart()->getTimestamp()] = $shiftWithDate;
				}
			}
			if (count($shiftedShifts) > 1)
			{
				ksort($shiftedShifts);
				if ($checkRelevant)
				{
					foreach ($shiftedShifts as $shiftedShift)
					{
						/** @var ShiftWithDate $shiftedShift */
						if ($this->hasShiftPlan($shiftedShift))
						{
							return $shiftedShift;
						}
					}
				}
				return reset($shiftedShifts);
			}
			if (count($fixedShifts) > 0)
			{
				if (count($fixedShifts) > 1)
				{
					ksort($fixedShifts);
				}
				return reset($fixedShifts);
			}
			return empty($shiftedShifts) ? null : reset($shiftedShifts);
		}
	}

	public function buildRelevantShiftWithDate(\DateTime $userDateTime, ?ShiftWithDate $previousShitWithDate = null): ?ShiftWithDate
	{
		return $this->buildShiftWithDate($userDateTime, true, $previousShitWithDate);
	}

	public function buildNextShiftWithDate(\DateTime $userDateTime, ?ShiftWithDate $previousShitWithDate = null): ?ShiftWithDate
	{
		return $this->buildShiftWithDate($userDateTime, false, $previousShitWithDate);
	}

	private function hasShiftPlan(ShiftWithDate $shift)
	{
		$shiftStartDate = clone $shift->getDateTimeStart();
		$shiftStartDate->setTimezone(new \DateTimeZone('UTC'));
		return (bool)$this->shiftPlanProvider->findActiveByComplexId(
			$shift->getShift()->getId(),
			$this->userId,
			new Date($shiftStartDate->format('Y-m-d'), 'Y-m-d')
		);
	}

	private function calculateShiftsWithDate(Schedule $schedule, \DateTime $userDateTime, $checkRelevant = true, $daysToCheck = 0, ?ShiftWithDate $previousShiftWithDate = null): array
	{
		$possibleShifts = [];

		$periodIterator = $this->buildDatesIterator($userDateTime, $daysToCheck);

		foreach ($periodIterator as $date)
		{
			foreach ($this->getShiftsByDate($schedule, $date) as $shift)
			{
				$shift = new ShiftWithDate($shift, $schedule, $date);
				if ($previousShiftWithDate)
				{
					if ($previousShiftWithDate->isEqualsTo($shift) ||
						$shift->getDateTimeStart()->getTimestamp() < $previousShiftWithDate->getDateTimeStart()->getTimestamp())
					{
						continue;
					}
				}
				if ($shift->endedByTime($userDateTime) || ($checkRelevant && !$shift->isEligibleToStart($userDateTime)))
				{
					continue;
				}

				$possibleShifts[] = $shift;
			}
		}
		return $possibleShifts;
	}

	public function getScheduleToStart($userDateTime)
	{
		if ($this->activeSchedules->hasFlextime())
		{
			foreach ($this->activeSchedules->getAll() as $schedule)
			{
				if ($schedule->isFlextime())
				{
					return $schedule;
				}
			}
		}
		if ($shiftDate = $this->buildRelevantShiftWithDate($userDateTime))
		{
			return $shiftDate->getSchedule();
		}
		if ($shiftDate = $this->buildNextShiftWithDate($userDateTime))
		{
			return $shiftDate->getSchedule();
		}
		return $this->activeSchedules->count() === 0 ? null : reset($this->activeSchedules->getAll());
	}

	private function getShiftsByDate(Schedule $schedule, \DateTime $date): array
	{
		if ($schedule->isShifted())
		{
			return $schedule->obtainActiveShifts();
		}
		if ($schedule->isFixed())
		{
			$weekDay = TimeHelper::getInstance()->getDayOfWeek($date);
			return array_filter([$schedule->getShiftByWeekDay($weekDay)]);
		}
		return [];
	}

	private function getSearchDaysForNextShift(Schedule $schedule): int
	{
		if ($schedule->isShifted())
		{
			return 2;
		}
		if ($schedule->isFixed())
		{
			return 8;
		}
		return 0;
	}

	private function getSearchDaysForRelevantShift(Schedule $schedule)
	{
		if ($schedule->isShifted())
		{
			return 2;
		}
		if ($schedule->isFixed())
		{
			return 2;
		}
		return 0;
	}

	public function buildRelevantRecordShiftWithDate(\DateTime $start, ?Schedule $schedule, ?Shift $shift): ?ShiftWithDate
	{
		if (!$schedule || !$shift)
		{
			return null;
		}
		foreach ($this->buildDatesIterator($start, $this->getSearchDaysForRelevantShift($schedule)) as $date)
		{
			$shiftWithDate = new ShiftWithDate($shift, $schedule, $date);
			if ($shiftWithDate->isEligibleToStart($start))
			{
				return $shiftWithDate;
			}
		}
		return null;
	}

	private function buildDatesIterator(\DateTime $userDateTime, $daysToCheck)
	{
		$yesterdayUserDateTime = clone $userDateTime;
		$yesterdayUserDateTime->sub(new \DateInterval('P1D'));
		return TimeHelper::getInstance()->buildDatesIterator($yesterdayUserDateTime, $daysToCheck);
	}

	/**
	 * @param \DateTime $from
	 * @param \DateTime $to
	 * @return ShiftWithDate[]
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function buildShiftWithDates(\DateTime $from, \DateTime $to)
	{
		$workingShifts = [];
		$periodIterator = TimeHelper::getInstance()->buildDatesIterator($from, $to);
		foreach ($periodIterator as $date)
		{
			foreach ($this->activeSchedules as $schedule)
			{
				foreach ($this->getShiftsByDate($schedule, $date) as $shift)
				{
					$shiftWithDate = new ShiftWithDate($shift, $schedule, $date);

					if ($schedule->isShifted() && !$this->hasShiftPlan($shiftWithDate))
					{
						continue;
					}
					$workingShifts[] = $shiftWithDate;
				}
			}
		}
		return $workingShifts;
	}
}