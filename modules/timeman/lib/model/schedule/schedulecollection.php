<?php
namespace Bitrix\Timeman\Model\Schedule;

class ScheduleCollection extends EO_Schedule_Collection
{
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
}