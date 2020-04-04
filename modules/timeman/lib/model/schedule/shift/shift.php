<?php
namespace Bitrix\Timeman\Model\Schedule\Shift;

use Bitrix\Timeman\Helper\TimeDictionary;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;

class Shift extends EO_Shift
{
	/** @var TimeHelper */
	private $timeHelper;

	public static function create($scheduleId, $name, $start, $end, $breakDuration = null, $workDays = null)
	{
		$shift = new static($setDefaultValues = false);
		$shift->setScheduleId($scheduleId);
		$shift->setName($name);
		$shift->setWorkTimeStart($start);
		$shift->setWorkTimeEnd($end);
		$shift->setWorkDays($workDays);
		if ($breakDuration !== null)
		{
			$shift->setBreakDuration($breakDuration);
		}
		return $shift;
	}

	public static function isDateInShiftWorkDays($dateTime, $shift)
	{
		return $shift && in_array(TimeHelper::getInstance()->getDayOfWeek($dateTime), array_map('intval', str_split($shift['WORK_DAYS'])), true);
	}

	public function edit($name, $startTime, $endTime, $breakDuration, $workdays)
	{
		$this->setName($name);
		$this->setWorkTimeStart($startTime);
		$this->setWorkTimeEnd($endTime);
		$this->setBreakDuration($breakDuration);
		$this->setWorkDays($workdays);
	}

	public static function getShiftDuration($shift)
	{
		$duration = $shift['WORK_TIME_END'] - $shift['WORK_TIME_START'];
		if ($duration < 0)
		{
			$duration = 24 * TimeDictionary::SECONDS_PER_HOUR - $shift['WORK_TIME_START'] + $shift['WORK_TIME_END'];
		}
		return $duration;
	}

	public function getDuration()
	{
		return static::getShiftDuration($this);
	}

	public function getStartHours()
	{
		return $this->getHours($this->getWorkTimeStart());
	}

	public function getEndHours()
	{
		return $this->getHours($this->getWorkTimeEnd());
	}

	private function getHours($secs)
	{
		return $this->getTimeHelper()->getHours($secs);
	}

	public function getStartMinutes()
	{
		return $this->getMinutes($this->getWorkTimeStart());
	}

	public function getEndMinutes()
	{
		return $this->getMinutes($this->getWorkTimeEnd());
	}

	private function getMinutes($secs)
	{
		return $this->getTimeHelper()->getMinutes($secs);
	}

	public function getStartSeconds()
	{
		return $this->getSeconds($this->getWorkTimeStart());
	}

	public function getEndSeconds()
	{
		return $this->getSeconds($this->getWorkTimeEnd());
	}

	private function getSeconds($secs)
	{
		return $this->getTimeHelper()->getSeconds($secs);
	}

	private function getTimeHelper()
	{
		return TimeHelper::getInstance();
	}

	/**
	 * @return Schedule|null
	 */
	public function obtainSchedule()
	{
		try
		{
			return $this->get('SCHEDULE');
		}
		catch (\Exception $exc)
		{
			return null;
		}
	}

	public function isForWeekDay($weekDay)
	{
		return in_array((int)$weekDay, array_map('intval', str_split($this->getWorkDays())), true);
	}
}