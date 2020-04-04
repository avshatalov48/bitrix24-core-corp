<?php
namespace Bitrix\Timeman\Service\Worktime\Violation;

use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;

class WorktimeViolationResult extends WorktimeServiceResult
{
	const ERROR_CODE_SHIFT_PLAN_NOT_FOUND = 'SHIFT_PLAN_NOT_FOUND';
	const ERROR_CODE_SHIFT_NOT_FOUND = 'SHIFT_NOT_FOUND';
	const ERROR_CODE_WRONG_PARAMETERS = 'WRONG_PARAMETERS';
	const ERROR_CODE_NOT_IMPLEMENTED_YET = 'NOT_IMPLEMENTED_YET';
	const ERROR_CODE_VIOLATION_NOT_UNDER_CONTROL = 'VIOLATION_NOT_UNDER_CONTROL';
	const ERROR_CODE_NO_VIOLATION = 'NO_VIOLATION';
	const ERROR_CODE_SHIFTS_DAYS_INTERSECT = 'SHIFT_DAYS_INTERSECT';
	const ERROR_CODE_INVALID_SHIFT_DURATION = 'INVALID_SHIFT_DURATION';
	const ERROR_CODE_NO_USERS_ASSIGNED_TO_SCHEDULE = 'NO_USERS_ASSIGNED_TO_SCHEDULE';

	/** @var WorktimeViolation[] */
	private $violations = [];
	private $shift;
	private $schedule;
	private $shiftPlan;

	/**
	 * @return WorktimeViolation[]
	 */
	public function getViolations()
	{
		return $this->violations;
	}

	public function getFirstViolation()
	{
		return empty($this->violations) ? null : reset($this->violations);
	}

	/**
	 * @param WorktimeViolation[] $violations
	 * @return WorktimeViolationResult
	 */
	public function setViolations($violations)
	{
		$this->violations = $violations;
		return $this;
	}

	public function addViolation(WorktimeViolation $violation)
	{
		if ($this->violations === null)
		{
			$this->violations = [];
		}
		$this->violations[] = $violation;
		return $this;
	}

	/**
	 * @return Shift
	 */
	public function getShift()
	{
		return $this->shift;
	}

	/**
	 * @return Schedule
	 */
	public function getSchedule()
	{
		return $this->schedule;
	}

	public function setSchedule($schedule)
	{
		$this->schedule = $schedule;
		return $this;
	}

	/**
	 * @return ShiftPlan
	 */
	public function getShiftPlan()
	{
		return $this->shiftPlan;
	}

	/**
	 * @param ShiftPlan $shiftPlan
	 * @return $this
	 */
	public function setShiftPlan($shiftPlan)
	{
		$this->shiftPlan = $shiftPlan;
		return $this;
	}

	/**
	 * @param Shift $shift
	 * @return $this
	 */
	public function setShift($shift)
	{
		$this->shift = $shift;
		return $this;
	}
}