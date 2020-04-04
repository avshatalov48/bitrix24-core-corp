<?php

namespace Bitrix\Disk\Volume;

/**
 * Disk timer class.
 * @package Bitrix\Disk\Volume
 */
class Timer implements IVolumeTimeLimit
{
	/** @var float microseconds */
	private $timeLimit = -1;

	/** @var int seconds */
	private $timeGap = 5;

	/** @var float microseconds */
	private $startTime = -1;

	/** @var boolean */
	private $timeLimitReached = false;

	/** @const int General limitation execution time. */
	const MAX_EXECUTION_TIME = 30;

	/** @var string Step id */
	private $stepId;

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
	public function startTimer($startTimestamp = 0)
	{
		if ($startTimestamp > 0)
		{
			$this->startTime = $startTimestamp;
		}
		else
		{
			$this->startTime = microtime(true) * 1000;
		}
	}

	/**
	 * Checks timer for time limitation.
	 * @return bool
	 */
	public function checkTimeEnd()
	{
		if ($this->timeLimit > 0)
		{
			$currentTime = microtime(true) * 1000;
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
	public function hasTimeLimitReached()
	{
		return $this->timeLimitReached;
	}

	/**
	 * Gets limitation time in seconds.
	 * @return int
	 */
	public function getTimeLimit()
	{
		return $this->timeLimit / 1000;
	}

	/**
	 * Sets limitation time in seconds.
	 * @param int $timeLimit Timeout in seconds.
	 * @return void
	 */
	public function setTimeLimit($timeLimit)
	{
		$this->timeLimit = $timeLimit * 1000;
	}

	/**
	 * Gets step identification.
	 * @return string|null
	 */
	public function getStepId()
	{
		return $this->stepId;
	}

	/**
	 * Sets step identification.
	 * @param string|null $stepId Step id.
	 * @return void
	 */
	public function setStepId($stepId)
	{
		$this->stepId = $stepId;
	}
}

