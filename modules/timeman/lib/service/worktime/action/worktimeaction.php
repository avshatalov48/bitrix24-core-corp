<?php
namespace Bitrix\Timeman\Service\Worktime\Action;

use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;

class WorktimeAction
{
	private const TYPE_START = 'START';
	private const TYPE_PAUSE = 'PAUSE';
	private const TYPE_CONTINUE = 'CONTINUE';
	private const TYPE_REOPEN = 'RELAUNCH';
	private const TYPE_STOP = 'STOP';
	private const TYPE_EDIT = 'EDIT';
	private const TYPE_APPROVE = 'APPROVE';

	/** @var Schedule */
	private $schedule = null;
	/** @var Shift */
	private $shift = null;
	/** @var WorktimeRecord */
	private $record = null;
	/** @var WorktimeRecordManager */
	private $recordManager;
	/** @var ShiftsManager */
	private $shiftsManager;

	/** @var string */
	private $type;
	/** @var int */
	private $userId;

	private function __construct($userId, $type)
	{
		$this->userId = $userId;
		$this->type = $type;
	}

	public static function createStartAction($userId)
	{
		return new static($userId, static::TYPE_START);
	}

	public static function createStopAction($userId)
	{
		return new static($userId, static::TYPE_STOP);
	}

	public static function createPauseAction($userId)
	{
		return new static($userId, static::TYPE_PAUSE);
	}

	public static function createContinueAction($userId)
	{
		return new static($userId, static::TYPE_CONTINUE);
	}

	public static function createEditAction($userId)
	{
		return new static($userId, static::TYPE_EDIT);
	}

	public static function createReopenAction($userId)
	{
		return new static($userId, static::TYPE_REOPEN);
	}

	public static function createApproveAction($userId)
	{
		return new static($userId, static::TYPE_APPROVE);
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
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
	 * @return WorktimeAction
	 */
	public function setSchedule($schedule)
	{
		$this->schedule = $schedule;
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
	 * @param Shift|null $shift
	 * @return WorktimeAction
	 */
	public function setShift($shift)
	{
		$this->shift = $shift;
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
	 * @param WorktimeRecord $record
	 * @return WorktimeAction
	 */
	public function setRecord($record)
	{
		$this->record = $record;
		return $this;
	}

	public function isStart()
	{
		return $this->type === static::TYPE_START;
	}

	public function isPause()
	{
		return $this->type === static::TYPE_PAUSE;
	}

	public function isContinue()
	{
		return $this->type === static::TYPE_CONTINUE;
	}

	public function isReopen()
	{
		return $this->type === static::TYPE_REOPEN;
	}

	public function isStop()
	{
		return $this->type === static::TYPE_STOP;
	}

	public function isEdit()
	{
		return $this->type === static::TYPE_EDIT;
	}

	public function getRecordManager(): ?WorktimeRecordManager
	{
		return $this->recordManager;
	}

	/**
	 * @param WorktimeRecordManager $recordManager
	 * @return WorktimeAction
	 */
	public function setRecordManager(?WorktimeRecordManager $recordManager): WorktimeAction
	{
		$this->recordManager = $recordManager;
		return $this;
	}

	public function setShiftsManager(ShiftsManager $shiftsManager)
	{
		$this->shiftsManager = $shiftsManager;
		return $this;
	}

	public function getShiftsManager(): ?ShiftsManager
	{
		return $this->shiftsManager;
	}
}