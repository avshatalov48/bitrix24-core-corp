<?php
namespace Bitrix\Timeman\Model\Schedule;

use Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection;

class ScheduleCollection extends EO_Schedule_Collection
{
	public static function createFromArray($schedules)
	{
		$collection = new static();
		foreach ($schedules as $schedule)
		{
			if ($schedule instanceof Schedule)
			{
				$collection->add($schedule);
			}
		}
		return $collection;
	}

	public function getFirst()
	{
		if (empty($this->getIdList()))
		{
			return null;
		}
		return $this->getByPrimary(reset($this->getIdList()));
	}

	public function hasShifted()
	{
		foreach ($this->getAll() as $schedule)
		{
			if ($schedule->isShifted())
			{
				return true;
			}
		}
		return false;
	}

	public function obtainActiveShifts()
	{
		$shifts = new ShiftCollection();
		foreach ($this->getAll() as $schedule)
		{
			foreach ($schedule->obtainActiveShifts() as $shift)
			{
				$shifts->add($shift);
			}
		}
		return $shifts;
	}

	public function hasFlextime()
	{
		foreach ($this->getAll() as $schedule)
		{
			if ($schedule->isFlextime())
			{
				return true;
			}
		}
		return false;
	}

	public function obtainShiftById($shiftId)
	{
		foreach ($this->getAll() as $schedule)
		{
			if ($schedule->obtainShiftByPrimary($shiftId))
			{
				return $schedule->obtainShiftByPrimary($shiftId);
			}
		}
		return null;
	}
}