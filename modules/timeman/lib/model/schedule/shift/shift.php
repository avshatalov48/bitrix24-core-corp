<?php
namespace Bitrix\Timeman\Model\Schedule\Shift;

use Bitrix\Timeman\Helper\TimeDictionary;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;

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
		if ($shift instanceof Shift)
		{
			return $shift->getDuration();
		}
		return static::wakeUp($shift)->getDuration();
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
		return in_array((int)$weekDay, array_map('intval', str_split($this->getWorkDays())), true);
	}

	/**
	 * @param int $userId
	 * @param Schedule|null $schedule
	 */
	public function isEligibleToStartByTime($userDateTime, $schedule = null)
	{
		return $this->buildStartDateTimeByArrivalDateTime($userDateTime, $schedule) !== null;
	}

	/**
	 * @param \DateTime $userDateTime
	 * @param Schedule $schedule
	 * @return \DateTime|null
	 * @throws \Exception
	 */
	public function buildStartDateTimeByArrivalDateTime($userDateTime, $schedule)
	{
		$allowedStartOffset = 0;
		if ($schedule)
		{
			if ($schedule->isShifted())
			{
				if ($schedule->getAllowedMaxShiftStartOffset() > 0)
				{
					$allowedStartOffset = $schedule->getAllowedMaxShiftStartOffset();
				}
			}
			elseif ($schedule->isFixed())
			{
				$allowedStartOffset = Schedule::FIXED_MAX_START_OFFSET;
			}
		}
		$startEnds = $this->buildStartsEndsAroundDate($userDateTime);
		foreach ($startEnds as $startEnd)
		{
			/** @var \DateTime[] $startEnd */
			$currentStartDate = $startEnd[0];
			$shiftEndModified = $startEnd[1];

			if ($userDateTime->getTimestamp() >= ($currentStartDate->getTimestamp() - $allowedStartOffset)
				&& $userDateTime->getTimestamp() <= $shiftEndModified->getTimestamp())
			{
				return $currentStartDate;
			}
		}
		return null;
	}

	/**
	 * @param \DateTime $userDateTime
	 * @return array
	 * @throws \Exception
	 */
	public function buildStartsEndsAroundDate($userDateTime)
	{
		$result = [];
		$shiftTodayStart = clone $userDateTime;
		TimeHelper::getInstance()->setTimeFromSeconds($shiftTodayStart, $this->getWorkTimeStart());

		$shiftTodayEnd = clone $shiftTodayStart;
		$shiftTodayEnd->add(new \DateInterval('PT' . $this->getDuration() . 'S'));

		$intervals = ['', 'P1D', '+P1D'];
		foreach ($intervals as $interval)
		{
			$currentStartDate = clone $shiftTodayStart;
			$shiftEndModified = clone $shiftTodayEnd;

			if ($interval)
			{
				if (substr($interval, 0, 1) === '+')
				{
					$currentStartDate->add(new \DateInterval(substr($interval, 1)));
					$shiftEndModified->add(new \DateInterval(substr($interval, 1)));
				}
				else
				{
					$currentStartDate->sub(new \DateInterval($interval));
					$shiftEndModified->sub(new \DateInterval($interval));
				}
			}
			$result[TimeHelper::getInstance()->getDayOfWeek($currentStartDate)] = [$currentStartDate, $shiftEndModified];
		}
		return $result;
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
		return $this->buildUtcDateTimeBySecondsUserDate(
			$this->getWorkTimeStart(),
			$shiftPlan->getUserId(),
			$shiftPlan->getDateAssignedUtc()
		);
	}

	/**
	 * @param $seconds
	 * @param $userId
	 * @param \DateTime $userUtcDate
	 * @return \DateTime|null
	 */
	public function buildUtcDateTimeBySecondsUserDate($seconds, $userId, $userUtcDate)
	{
		$utcStartSeconds = $this->normalizeSeconds($seconds - TimeHelper::getInstance()->getUserUtcOffset($userId));
		$utcDate = TimeHelper::getInstance()->createDateTimeFromFormat('Y-m-d', $userUtcDate->format('Y-m-d'), 0);
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