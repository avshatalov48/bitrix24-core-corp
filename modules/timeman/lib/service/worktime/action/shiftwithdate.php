<?php
namespace Bitrix\Timeman\Service\Worktime\Action;

use Bitrix\Main\ArgumentException;
use Bitrix\Timeman\Helper\TimeDictionary;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;

class ShiftWithDate
{
	/** @var Shift */
	private $shift;
	/** @var \DateTime */
	private $dateTimeStart;
	/** @var Schedule */
	private $schedule;
	/** @var \DateTime */
	private $dateTimeEnd;

	public function __construct(Shift $shift, Schedule $schedule, \DateTime $dateTimeStart)
	{
		$this->shift = $shift;
		$this->schedule = $schedule;
		if ($this->schedule->isFlextime())
		{
			throw new ArgumentException('Wrong argument, Flexible schedules do not have shifts');
		}
		$this->dateTimeStart = clone $dateTimeStart;
		TimeHelper::getInstance()->setTimeFromSeconds($this->dateTimeStart, $this->shift->getWorkTimeStart());
		$this->dateTimeEnd = clone $this->dateTimeStart;
		$this->dateTimeEnd->add(new \DateInterval('PT' . $this->shift->getDuration() . 'S'));
	}

	public function isEligibleToStart(\DateTime $userDateTime)
	{
		if ($userDateTime->getTimestamp() >= $this->dateTimeStart->getTimestamp() - $this->getMaxStartOffset()
			&& $userDateTime->getTimestamp() <= $this->dateTimeEnd->getTimestamp() + $this->getMaxEndOffset()
		)
		{
			return true;
		}
		return false;
	}

	private function getMaxStartOffset()
	{
		if ($this->schedule->isFixed())
		{
			return $this->shift->getDuration() / 2;
		}
		if ($this->schedule->isShifted())
		{
			return $this->schedule->getAllowedMaxShiftStartOffset();
		}
		return 0;
	}

	private function getMaxEndOffset()
	{
		if ($this->schedule->isFixed())
		{
			return $this->shift->getDuration() / 2;
		}
		if ($this->schedule->isShifted())
		{
			return TimeDictionary::SECONDS_PER_HOUR;
		}
		return 0;
	}

	public function isEqualsTo(?ShiftWithDate $otherShiftWithDate)
	{
		if (!$otherShiftWithDate)
		{
			return false;
		}
		return $this->getShift()->getId() === $otherShiftWithDate->getShift()->getId()
			   &&
			   $this->getDateTimeStart()->getTimestamp() === $otherShiftWithDate->getDateTimeStart()->getTimestamp();
	}

	public function endedByTime(\DateTime $userNowDateTime)
	{
		return $userNowDateTime->getTimestamp() > $this->getDateTimeEnd()->getTimestamp() + $this->getMaxEndOffset();
	}

	public function getShift()
	{
		return $this->shift;
	}

	public function getDateTimeEnd()
	{
		return $this->dateTimeEnd;
	}

	public function getDateTimeStart()
	{
		return $this->dateTimeStart;
	}

	/**
	 * @return Schedule
	 */
	public function getSchedule(): Schedule
	{
		return $this->schedule;
	}
}