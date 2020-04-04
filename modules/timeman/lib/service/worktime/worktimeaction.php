<?php
namespace Bitrix\Timeman\Service\Worktime;

use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;

class WorktimeAction
{
	const TYPE_START = 'START';
	const TYPE_PAUSE = 'PAUSE';
	const TYPE_CONTINUE = 'CONTINUE';
	const TYPE_RELAUNCH = 'RELAUNCH';
	const TYPE_STOP = 'STOP';
	const TYPE_EDIT = 'EDIT';

	/** @var Schedule */
	private $schedule = null;
	/** @var Shift */
	private $shift = null;
	/** @var WorktimeRecord */
	private $record = null;

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
		$action = new static($userId, static::TYPE_START);
		return $action;
	}

	public static function createStopAction($userId)
	{
		$action = new static($userId, static::TYPE_STOP);
		return $action;
	}

	public static function createPauseAction($userId)
	{
		$action = new static($userId, static::TYPE_PAUSE);
		return $action;
	}

	public static function createContinueAction($userId)
	{
		$action = new static($userId, static::TYPE_CONTINUE);
		return $action;
	}

	public static function createEditAction($userId)
	{
		$action = new static($userId, static::TYPE_EDIT);
		return $action;
	}

	public static function createRelaunchAction($userId)
	{
		$action = new static($userId, static::TYPE_RELAUNCH);
		return $action;
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

	public function isRelaunch()
	{
		return $this->type === static::TYPE_RELAUNCH;
	}

	public function isStop()
	{
		return $this->type === static::TYPE_STOP;
	}

	public function isEdit()
	{
		return $this->type === static::TYPE_EDIT;
	}
}