<?php
namespace Bitrix\Timeman\Service\Worktime\Action;

use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;

class WorktimeRecordManager
{
	/** @var Shift|null */
	private $shift;
	/** @var Schedule|null */
	private $schedule;
	/** @var WorktimeRecord|null */
	private $record;
	/** @var \DateTime */
	private $currentDateTime;
	/** @var ShiftsManager */
	private $shiftsManager;

	public function __construct(WorktimeRecord $record, ?Schedule $schedule, ?Shift $shift, \DateTime $currentDateTime, ShiftsManager $shiftsManager)
	{
		$this->record = $record;
		$this->shift = $shift;
		$this->schedule = $schedule;
		$this->currentDateTime = clone $currentDateTime;
		$this->shiftsManager = $shiftsManager;
		return $this;
	}

	public function getRecommendedStopTimestamp(): ?int
	{
		if ($shift = $this->buildRecordShiftWithDate())
		{
			return $shift->getDateTimeEnd()->getTimestamp();
		}
		if ($nextShift = $this->buildNextClosestShiftWithDate())
		{
			return $this->buildRecordStartDateTime()->getTimestamp() + $nextShift->getShift()->getDuration();
		}
		return $this->buildRecordStartDateTime()->getTimestamp() + 9 * 3600;
	}

	public function isEligibleToReopen()
	{
		if ($this->record->getRecordedStopTimestamp() === 0)
		{
			return false;
		}
		if (!$this->schedule)
		{
			return false;
		}
		if ((!$this->schedule->isAllowedToReopenRecord() || $this->schedule->isFlextime()))
		{
			return false;
		}

		return !$this->moreThanHalfTillNextShiftOrNewShiftStarted();
	}

	public function isEligibleToEdit()
	{
		if ($this->schedule && !$this->schedule->isAllowedToEditRecord())
		{
			return false;
		}
		if ($this->isRecordExpired())
		{
			return true;
		}

		return !$this->moreThanHalfTillNextShiftOrNewShiftStarted();
	}

	public function isRecordExpired()
	{
		if ($this->record->getRecordedStopTimestamp() > 0)
		{
			return false;
		}
		return $this->moreThanHalfTillNextShiftOrNewShiftStarted();
	}

	private function moreThanHalfTillNextShiftOrNewShiftStarted()
	{
		if ($this->shift)
		{
			if ($this->schedule && $this->schedule->isShifted())
			{
				return $this->currentDateTime->getTimestamp() > ($this->getRecommendedStopTimestamp() + 3600);
			}
			return !$this->lessThanHalfBetweenShifts();
		}
		elseif ($this->schedule && !$this->schedule->isFlextime())
		{
			if ($nextShift = $this->buildNextClosestShiftWithDate())
			{
				if ($nextShift->getDateTimeStart()->getTimestamp() - $this->record->getRecordedStartTimestamp() < 24 * 3600)
				{
					$shiftDuration = $nextShift->getShift()->getDuration();
					return $this->currentDateTime->getTimestamp() > ($nextShift->getDateTimeStart()->getTimestamp() - $shiftDuration / 2);
				}
			}
			return $this->currentDateTime->getTimestamp() > ($this->record->getRecordedStartTimestamp() + 2 * 9 * 3600);
		}

		return $this->currentDateTime->getTimestamp() >= $this->buildStartPlusTwoDays()->getTimestamp();
	}

	private function buildRecordShiftWithDate(): ?ShiftWithDate
	{
		return $this->shiftsManager->buildRelevantRecordShiftWithDate($this->buildRecordStartDateTime(), $this->schedule, $this->shift);
	}

	public function isEligibleToStop()
	{
		return $this->record->getRecordedStopTimestamp() === 0;
	}

	public function isEligibleToPause()
	{
		if ($this->record->getRecordedStopTimestamp() === 0 && !$this->isRecordExpired() && !$this->record->isPaused())
		{
			return true;
		}
		return false;
	}

	public function isEligibleToContinue()
	{
		return $this->record->isPaused() && !$this->isRecordExpired();
	}

	private function buildStartPlusTwoDays()
	{
		$startDate = $this->buildRecordStartDateTime();
		$startDate->add(new \DateInterval('P2D'));
		$startDate->setTime(0, 0, 0);
		return $startDate;
	}

	private function lessThanHalfBetweenShifts()
	{
		$idealRecordEnd = $this->getRecommendedStopTimestamp();
		if ($nextShift = $this->buildNextClosestShiftWithDate())
		{
			$interval = $nextShift->getDateTimeStart()->getTimestamp() - $idealRecordEnd;
			if ($this->buildRecordShiftWithDate())
			{
				$shiftDuration = $this->buildRecordShiftWithDate()->getShift()->getDuration();
			}
			else
			{
				$shiftDuration = $nextShift->getShift()->getDuration();
			}
			$maxInterval = min($interval / 2, $shiftDuration);
			return $this->currentDateTime->getTimestamp() < ($idealRecordEnd + $maxInterval);
		}

		return $this->currentDateTime->getTimestamp() < $this->buildStartPlusTwoDays()->getTimestamp();
	}

	private function buildNextClosestShiftWithDate()
	{
		return $this->shiftsManager->buildNextShiftWithDate($this->buildRecordStartDateTime(), $this->buildRecordShiftWithDate());
	}

	public function getRecordSchedule()
	{
		return $this->schedule;
	}

	public function getRecordShift()
	{
		return $this->shift;
	}

	public function getRecord(): ?WorktimeRecord
	{
		return $this->record;
	}

	public function buildStopTimestampForAutoClose()
	{
		if (!($this->schedule instanceof Schedule))
		{
			return null;
		}
		if ($this->shift)
		{
			return $this->getRecommendedStopTimestamp();
		}
		if ($this->schedule && $this->schedule->isShifted())
		{
			/** @var Shift $firstRandomShift */
			$shifts = $this->schedule->obtainShifts();
			$firstRandomShift = reset($shifts);
			if (!$firstRandomShift)
			{
				return null;
			}
			return $this->record->getRecordedStartTimestamp() + $firstRandomShift->getDuration();
		}
		return null;
	}

	public function getSchedule()
	{
		return $this->schedule;
	}

	private function buildRecordStartDateTime()
	{
		if ($this->record->buildRecordedStartDateTime() === null)
		{
			return TimeHelper::getInstance()->createUserDateTimeFromFormat('U', $this->record->getDateStart()->getTimestamp(), $this->record->getUserId());
		}
		return $this->record->buildRecordedStartDateTime();
	}
}