<?php
namespace Bitrix\Timeman\Service\Worktime\Result;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Service\BaseServiceResult;


class WorktimeServiceResult extends BaseServiceResult
{
	const ERROR_FOR_USER = 'ERROR_FOR_USER';
	const ERROR_NOTHING_TO_START = 'ERROR_NOTHING_TO_START';
	const ERROR_REASON_NEEDED = 'ERROR_REPORT_NEEDED';
	const ERROR_EXPIRED_REASON_NEEDED = 'ERROR_EXPIRED_REASON_NEEDED';

	/** @var WorktimeEvent[] */
	private $worktimeEvents;
	/** @var WorktimeRecord */
	private $worktimeRecord;
	/** @var Schedule */
	private $schedule;
	/** @var Shift */
	private $shift;


	/**
	 * @return WorktimeRecord
	 */
	public function getWorktimeRecord()
	{
		return $this->worktimeRecord;
	}

	/**
	 * @param mixed $worktimeRecord
	 * @return WorktimeServiceResult
	 */
	public function setWorktimeRecord($worktimeRecord)
	{
		$this->worktimeRecord = $worktimeRecord;
		return $this;
	}

	/**
	 * @param $worktimeEvents
	 * @return WorktimeServiceResult
	 */
	public function setWorktimeEvents($worktimeEvents)
	{
		$this->worktimeEvents = $worktimeEvents;
		return $this;
	}

	public function getWorktimeEvent()
	{
		return reset($this->worktimeEvents);
	}

	/**
	 * @param WorktimeEvent $worktimeEvent
	 * @return WorktimeServiceResult
	 */
	public function setWorktimeEvent($worktimeEvent)
	{
		$this->worktimeEvents[] = $worktimeEvent;
		return $this;
	}

	public function addRecordNotFoundError()
	{
		$this->addError(new Error(Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_WORKTIME_RECORD_NOT_FOUND')));
		return $this;
	}

	public function addScheduleNotFoundError($code = 0)
	{
		$this->addError(new Error(Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_SCHEDULE_NOT_FOUND'), $code));
		return $this;
	}

	public function addReasonNeededError()
	{
		$this->addError(new Error(Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_REASON_IS_REQUIRED'), static::ERROR_REASON_NEEDED));
		return $this;
	}

	public function addProhibitedActionError($code = 0)
	{
		$this->addError(new Error(Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_PROHIBITED_ACTION'), $code));
		return $this;
	}

	/**
	 * @return Error|null
	 */
	public function getFirstError()
	{
		return !empty(reset($this->getErrors())) ? reset($this->getErrors()) : null;
	}

	/**
	 * @return WorktimeEvent[]
	 */
	public function getWorktimeEvents()
	{
		return $this->worktimeEvents;
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

	/**
	 * @param Shift $shift
	 * @return $this
	 */
	public function setShift($shift)
	{
		$this->shift = $shift;
		return $this;
	}

	/**
	 * @return Shift|null
	 */
	public function getShift()
	{
		return $this->shift;
	}
}