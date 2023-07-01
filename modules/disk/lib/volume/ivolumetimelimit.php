<?php

namespace Bitrix\Disk\Volume;

interface IVolumeTimeLimit
{
	/**
	 * Sets start up time.
	 * @return void
	 */
	public function startTimer(): void;

	/**
	 * Checks timer for time limitation.
	 * @return bool
	 */
	public function checkTimeEnd(): bool;

	/**
	 * Tells true if time limit reached.
	 * @return boolean
	 */
	public function hasTimeLimitReached(): bool;

	/**
	 * Gets limitation time in seconds.
	 * @return int
	 */
	public function getTimeLimit(): int;

	/**
	 * Sets limitation time in seconds.
	 * @param int $timeLimit Timeout in seconds.
	 * @return static
	 */
	public function setTimeLimit(int $timeLimit): self;
}
