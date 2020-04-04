<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Disk\Volume;

/**
 * Timer.
 * @implements Volume\IVolumeTimeLimit
 */
trait TimeLimit // implements Volume\IVolumeTimeLimit
{
	/** @var Volume\Timer */
	private $timer;

	/** @var bool */
	private $isCronRun = false;

	/**
	 * Gets timer.
	 * @return Volume\Timer
	 */
	public function instanceTimer()
	{
		if (!($this->timer instanceof Volume\Timer))
		{
			$this->timer = new Volume\Timer();

			// cron run
			$this->isCronRun =
				!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24') &&
				(php_sapi_name() === 'cli');

			if ($this->isCronRun)
			{
				// increase time limit for cron running task up to 10 minutes
				$this->timer->setTimeLimit(Volume\Timer::MAX_EXECUTION_TIME * 20);
			}
		}

		return $this->timer;
	}

	/**
	 * Sets start up time.
	 * @return void
	 */
	public function startTimer()
	{
		// running on hint
		if ($this->isCronRun === false && defined('START_EXEC_TIME') && START_EXEC_TIME > 0)
		{
			$this->instanceTimer()->startTimer(START_EXEC_TIME);
		}
		else
		{
			$this->instanceTimer()->startTimer();
		}
	}

	/**
	 * Checks timer for time limitation.
	 * @return bool
	 */
	public function checkTimeEnd()
	{
		return $this->instanceTimer()->checkTimeEnd();
	}

	/**
	 * Tells true if time limit reached.
	 * @return boolean
	 */
	public function hasTimeLimitReached()
	{
		return $this->instanceTimer()->hasTimeLimitReached();
	}

	/**
	 * Sets limitation time in seconds.
	 * @param int $timeLimit Timeout in seconds.
	 * @return $this
	 */
	public function setTimeLimit($timeLimit)
	{
		$this->instanceTimer()->setTimeLimit($timeLimit);

		return $this;
	}

	/**
	 * Gets limitation time in seconds.
	 * @return int
	 */
	public function getTimeLimit()
	{
		return $this->instanceTimer()->getTimeLimit();
	}
}

