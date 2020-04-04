<?php
namespace Bitrix\Timeman\Service\Worktime\Violation;

use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;

class WorktimeViolationParams
{
	/** @var WorktimeEvent[] */
	private $worktimeEvents;
	/** @var Schedule */
	private $schedule;
	/** @var WorktimeRecord|[] */
	private $record;
	/** @var Shift */
	private $shift;
	/** @var ShiftPlan|null */
	private $shiftPlan;
	/** @var array */
	private $absenceData;
	/** @var callable */
	private $createViolationCallback;
	/** @var ViolationRules */
	private $violationRules;

	/**
	 * @return WorktimeEvent[]
	 */
	public function getWorktimeEvents()
	{
		return $this->worktimeEvents;
	}

	/**
	 * @param WorktimeEvent[] $worktimeEvents
	 * @return WorktimeViolationParams
	 */
	public function setWorktimeEvents($worktimeEvents)
	{
		$this->worktimeEvents = $worktimeEvents;
		return $this;
	}

	/**
	 * @return Schedule
	 */
	public function getSchedule()
	{
		return $this->schedule;
	}

	/**
	 * @param Schedule $schedule
	 * @return WorktimeViolationParams
	 */
	public function setSchedule($schedule)
	{
		$this->schedule = $schedule;
		return $this;
	}

	/**
	 * @return WorktimeRecord
	 */
	public function getRecord()
	{
		return $this->record;
	}

	/**
	 * @param WorktimeRecord|[] $record
	 * @return WorktimeViolationParams
	 */
	public function setRecord($record)
	{
		$this->record = $record;
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
	 * @param Shift $shift
	 * @return WorktimeViolationParams
	 */
	public function setShift($shift)
	{
		$this->shift = $shift;
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
	 * @return WorktimeViolationParams
	 */
	public function setShiftPlan($shiftPlan)
	{
		$this->shiftPlan = $shiftPlan;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getAbsenceData()
	{
		return $this->absenceData;
	}

	/**
	 * @param array $absenceData
	 * @return WorktimeViolationParams
	 */
	public function setAbsenceData($absenceData)
	{
		$this->absenceData = $absenceData;
		return $this;
	}

	/**
	 * @return callable
	 */
	public function getCreateViolationCallback()
	{
		return $this->createViolationCallback;
	}

	/**
	 * @param callable $createViolationCallback
	 * @return WorktimeViolationParams
	 */
	public function setCreateViolationCallback($createViolationCallback)
	{
		$this->createViolationCallback = $createViolationCallback;
		return $this;
	}

	/**
	 * @return ViolationRules
	 */
	public function getViolationRules()
	{
		return $this->violationRules;
	}

	/**
	 * @param ViolationRules $violationRules
	 * @return WorktimeViolationParams
	 */
	public function setViolationRules($violationRules)
	{
		$this->violationRules = $violationRules;
		return $this;
	}
}