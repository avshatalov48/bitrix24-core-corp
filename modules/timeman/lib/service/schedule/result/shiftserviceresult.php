<?php
namespace Bitrix\Timeman\Service\Schedule\Result;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Service\BaseServiceResult;

class ShiftServiceResult extends BaseServiceResult
{
	/** @var Shift $shift */
	private $shift;
	/** @var Schedule */
	private $schedule;

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
}