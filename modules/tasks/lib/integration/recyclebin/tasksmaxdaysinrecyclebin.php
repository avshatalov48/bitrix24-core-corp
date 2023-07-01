<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2022 Bitrix
 */

namespace Bitrix\Tasks\Integration\Recyclebin;

use Bitrix\Main\Type\DateTime;

class TasksMaxDaysInRecycleBin
{
	private int $maxDaysTTL;
	private const SECONDS_IN_DAY = 86400;

	public function __construct(int $maxDaysTTL = 31)
	{
		$this->maxDaysTTL = $maxDaysTTL;
	}

	public function getValue(): int
	{
		return $this->maxDaysTTL;
	}

	public function isOlderThanMaxTTL(DateTime $taskAddedAt): bool
	{
		return $taskAddedAt->getTimestamp() < $this->getAsDateTimeFromNow()->getTimestamp();
	}

	public function getAsDateTimeFromNow(): DateTime
	{
		return DateTime::createFromTimestamp(
			(time() + \CTimeZone::getOffset()) - (self::SECONDS_IN_DAY * $this->getValue())
		);
	}
}