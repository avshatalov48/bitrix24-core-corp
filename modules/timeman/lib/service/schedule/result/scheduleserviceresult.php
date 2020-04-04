<?php
namespace Bitrix\Timeman\Service\Schedule\Result;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Service\BaseServiceResult;

class ScheduleServiceResult extends BaseServiceResult
{
	/** @var Schedule */
	private $schedule;
	/** @var Shift[] $shifts */
	private $shifts;

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
	 * @return Schedule
	 */
	public function getSchedule()
	{
		return $this->schedule;
	}

	/**
	 * @param Schedule $schedule
	 * @return $this
	 */
	public function setSchedule($schedule)
	{
		$this->schedule = $schedule;
		return $this;
	}

	public function addScheduleNotFoundError()
	{
		$this->addError(new Error(Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_SCHEDULE_NOT_FOUND')));
		return $this;
	}
}