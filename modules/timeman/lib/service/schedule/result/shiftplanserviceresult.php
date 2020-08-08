<?php
namespace Bitrix\Timeman\Service\Schedule\Result;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Service\BaseServiceResult;
use Bitrix\Timeman\Service\Worktime\Action\ShiftWithDate;

class ShiftPlanServiceResult extends BaseServiceResult
{
	const ERROR_CODE_OVERLAPPING_SHIFT_PLAN = 'OVERLAPPING_SHIFT_PLAN';
	/** @var Shift $shift */
	private $shift;
	/** @var ShiftPlan $shiftPlan */
	private $shiftPlan;
	/** @var Schedule */
	private $schedule;
	/** @var ShiftWithDate */
	private $shiftWithDate;

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
	 * @return Shift
	 */
	public function getShift()
	{
		return $this->shift;
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

	public function addShiftNotFoundError()
	{
		$this->addError(new Error(Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_SHIFT_NOT_FOUND')));
		return $this;
	}

	public function addShiftPlanNotFoundError()
	{
		$this->addError(new Error(Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_SHIFT_PLAN_NOT_FOUND')));
		return $this;
	}

	public function addScheduleNotFoundError()
	{
		$this->addError(new Error(Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_SCHEDULE_NOT_FOUND')));
		return $this;
	}

	public function getSchedule()
	{
		return $this->schedule;
	}

	/**
	 * @param Schedule $schedule
	 */
	public function setSchedule($schedule)
	{
		$this->schedule = $schedule;
		return $this;
	}

	public function setShiftWithDate(ShiftWithDate $shiftWithDate)
	{
		$this->shiftWithDate = $shiftWithDate;
		return $this;
	}

	/**
	 * @return ShiftWithDate
	 */
	public function getShiftWithDate(): ?ShiftWithDate
	{
		return $this->shiftWithDate;
	}
}