<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Disk\Volume;

/**
 * Timer.
 * @implements Volume\IVolumeTimeLimit
 */
trait TimeLimit // implements Volume\IVolumeTimeLimit
{
	private ?Volume\Timer $timer = null;

	private bool $isCronRun = false;

	/**
	 * Gets timer.
	 * @return Volume\Timer
	 */
	public function instanceTimer(): Volume\Timer
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
	public function setTimer(Volume\Timer $timer): void
	{
		$this->timer = $timer;
	}

	/**
	 * Sets start up time.
	 * @return void
	 */
	public function startTimer(): void
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
	public function checkTimeEnd(): bool
	{
		return $this->instanceTimer()->checkTimeEnd();
	}

	/**
	 * Tells true if time limit reached.
	 * @return boolean
	 */
	public function hasTimeLimitReached(): bool
	{
		return $this->instanceTimer()->hasTimeLimitReached();
	}

	/**
	 * Sets limitation time in seconds.
	 * @param int $timeLimit Timeout in seconds.
	 * @return static
	 */
	public function setTimeLimit(int $timeLimit): self
	{
		$this->instanceTimer()->setTimeLimit($timeLimit);

		return $this;
	}

	/**
	 * Gets limitation time in seconds.
	 * @return int
	 */
	public function getTimeLimit(): int
	{
		return $this->instanceTimer()->getTimeLimit();
	}
}

