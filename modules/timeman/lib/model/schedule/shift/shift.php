<?php
namespace Bitrix\Timeman\Model\Schedule\Shift;

use Bitrix\Timeman\Helper\TimeDictionary;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;

class Shift extends EO_Shift
{
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

	public function getDuration()
	{
		$duration = $this->getWorkTimeEnd() - $this->getWorkTimeStart();
		if ($duration < 0)
		{
			$duration = 24 * TimeDictionary::SECONDS_PER_HOUR - $this->getWorkTimeStart() + $this->getWorkTimeEnd();
		}
		return $duration;
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
		return TimeHelper::getInstance()->getHours($secs);
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
		return TimeHelper::getInstance()->getMinutes($secs);
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
		return TimeHelper::getInstance()->getSeconds($secs);
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

	public function isForTime($seconds, $offset = 0)
	{
		if ($offset < 0)
		{
			$offset = 0;
		}
		$allowedStart = $this->normalizeSeconds($this->getWorkTimeStart() - $offset);
		if ($allowedStart <= $this->getWorkTimeEnd())
		{
			return $seconds >= $allowedStart && $seconds <= $this->getWorkTimeEnd();
		}
		if ($seconds >= $allowedStart && $seconds <= TimeDictionary::SECONDS_PER_DAY)
		{
			return true;
		}
		if ($seconds >= 0 && $seconds <= $this->getWorkTimeEnd())
		{
			return true;
		}

		return false;
	}

	public function isForWeekDay($weekDay)
	{
		return in_array(
			(int) $weekDay,
			array_map('intval', str_split($this->getWorkDays() ?? '')),
			true
		);
	}

	/**
	 * @param ShiftPlan $shiftPlan
	 * @return \DateTime
	 */
	public function buildUtcEndByShiftplan($shiftPlan)
	{
		$utcStart = $this->buildUtcStartByShiftplan($shiftPlan);
		$utcStart->add(new \DateInterval('PT' . $this->getDuration() . 'S'));
		return $utcStart;
	}

	/**
	 * @param ShiftPlan $shiftPlan
	 * @return \DateTime
	 */
	public function buildUtcStartByShiftplan($shiftPlan)
	{
		return $this->buildUtcStartByUserId(
			$shiftPlan->getUserId(),
			$shiftPlan->getDateAssignedUtc()
		);
	}

	/**
	 * @param $userSeconds
	 * @param $userId
	 * @param \DateTime $utcDateTime
	 * @return \DateTime|null
	 */
	public function buildUtcStartByUserId($userId, $utcDateTime)
	{
		$utcStartSeconds = $this->normalizeSeconds($this->getWorkTimeStart() - TimeHelper::getInstance()->getUserUtcOffset($userId));
		$utcDate = TimeHelper::getInstance()->createDateTimeFromFormat('Y-m-d', $utcDateTime->format('Y-m-d'), 0);
		TimeHelper::getInstance()->setTimeFromSeconds($utcDate, $utcStartSeconds);
		return $utcDate === false ? null : $utcDate;
	}

	private function normalizeSeconds($seconds)
	{
		return TimeHelper::getInstance()->normalizeSeconds($seconds);
	}

	public function isDeleted()
	{
		return $this->getDeleted();
	}

	public function isActive()
	{
		return !$this->isDeleted();
	}
}