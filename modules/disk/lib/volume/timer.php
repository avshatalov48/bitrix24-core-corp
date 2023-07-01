<?php

namespace Bitrix\Disk\Volume;

/**
 * Disk timer class.
 * @package Bitrix\Disk\Volume
 */
class Timer implements IVolumeTimeLimit
{
	/** @var int seconds */
	private $timeLimit = -1;

	/** @var int seconds */
	private $timeGap = 5;

	/** @var float seconds */
	private $startTime = -1;

	/** @var boolean */
	private $timeLimitReached = false;

	/** @const int General limitation execution time. */
	const MAX_EXECUTION_TIME = 30;


	/**
	 * @param int $timeLimit Timeout in seconds.
	 */
	public function __construct($timeLimit = -1)
	{
		if ($timeLimit > 0)
		{
			$this->setTimeLimit($timeLimit);
		}
		elseif (ini_get('max_execution_time') != '')
		{
			$executionTime = (int)ini_get('max_execution_time');
			if ($executionTime <= 0 || $executionTime > self::MAX_EXECUTION_TIME)
			{
				$executionTime = self::MAX_EXECUTION_TIME;
			}
			$this->setTimeLimit($executionTime - $this->timeGap);
		}
		else
		{
			$this->setTimeLimit(self::MAX_EXECUTION_TIME - $this->timeGap);
		}
	}

	/**
	 * Sets start up time.
	 * @param float $startTimestamp Start timestamp.
	 * @return void
	 */
	public function startTimer($startTimestamp = 0): void
	{
		if ($startTimestamp > 0)
		{
			$this->startTime = $startTimestamp;
		}
		else
		{
			$this->startTime = time();
		}
	}

	/**
	 * Checks timer for time limitation.
	 * @return bool
	 */
	public function checkTimeEnd(): bool
	{
		if ($this->timeLimit > 0 && $this->startTime > 0)
		{
			$currentTime = time();
			if (($currentTime - $this->startTime) >= $this->timeLimit)
			{
				$this->timeLimitReached = true;
				return false;
			}
		}

		return true;
	}

	/**
	 * Tells true if time limit reached.
	 * @return boolean
	 */
	public function hasTimeLimitReached(): bool
	{
		return $this->timeLimitReached;
	}

	/**
	 * Gets limitation time in seconds.
	 * @return int
	 */
	public function getTimeLimit(): int
	{
		return $this->timeLimit;
	}

	/**
	 * Sets limitation time in seconds.
	 * @param int $timeLimit Timeout in seconds.
	 * @return static
	 */
	public function setTimeLimit(int $timeLimit): self
	{
		$this->timeLimit = $timeLimit;

		return $this;
	}
}

