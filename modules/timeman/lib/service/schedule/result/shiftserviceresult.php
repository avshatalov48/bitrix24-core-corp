<?php
namespace Bitrix\Timeman\Service\Schedule\Result;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Service\BaseServiceResult;

class ShiftServiceResult extends BaseServiceResult
{
	/** @var Shift $shift */
	private $shift;
	/** @var Shift[] $shifts */
	private $shifts;
	/** @var ShiftPlan $shiftPlan */
	private $shiftPlan;

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
	 * @param Shift[] $shifts
	 * @return $this
	 */
	public function setShifts($shifts)
	{
		$this->shifts = $shifts;
		return $this;
	}

	/**
	 * @return Shift[]
	 */
	public function getShifts()
	{
		return $this->shifts;
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
}