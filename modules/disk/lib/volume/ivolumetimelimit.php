<?php

namespace Bitrix\Disk\Volume;


interface IVolumeTimeLimit
{
	/**
	 * Sets start up time.
	 * @return void
	 */
	public function startTimer();

	/**
	 * Checks timer for time limitation.
	 * @return bool
	 */
	public function checkTimeEnd();

	/**
	 * Tells true if time limit reached.
	 * @return boolean
	 */
	public function hasTimeLimitReached();

	/**
	 * Gets limitation time in seconds.
	 * @return int
	 */
	public function getTimeLimit();

	/**
	 * Sets limitation time in seconds.
	 * @param int $timeLimit Timeout in seconds.
	 * @return $this
	 */
	public function setTimeLimit($timeLimit);
}
