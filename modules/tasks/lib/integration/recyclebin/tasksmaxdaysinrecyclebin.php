<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2022 Bitrix
 */

namespace Bitrix\Tasks\Integration\Recyclebin;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;

class TasksMaxDaysInRecycleBin
{
	private const SECONDS_IN_DAY = 86400;
	private const SECONDS_IN_MINUTE = 60;
	private const SECONDS_IN_HOUR = 3600;
	private const MAX_DAYS_TTL = 31;

	public function getValue(): int
	{
		return static::MAX_DAYS_TTL;
	}

	public function isOlderThanMaxTTL(DateTime $taskAddedAt): bool
	{
		return $taskAddedAt->getTimestamp() < $this->getAsDateTimeFromNow()->getTimestamp();
	}

	public function getAsDateTimeFromNow(): DateTime
	{
		return DateTime::createFromTimestamp(
			(time() + \CTimeZone::getOffset()) - ($this->getTTL())
		);
	}

	public function getTTL(): int
	{
		$value = Option::get('tasks', 'tasks_recyclebin_ttl', static::MAX_DAYS_TTL);
		if ((int)$value === static::MAX_DAYS_TTL)
		{
			return self::SECONDS_IN_DAY * $value;
		}

		$parts = explode(' ', $value);
		if (count($parts) === 1)
		{
			return self::SECONDS_IN_DAY * $value;
		}

		[$value, $modifier] = $parts;

		if ($modifier === 's')
		{
			return $value;
		}

		if ($modifier === 'm')
		{
			return static::SECONDS_IN_MINUTE * $value;
		}

		if ($modifier === 'h')
		{
			return static::SECONDS_IN_HOUR * $value;
		}

		return self::SECONDS_IN_DAY * $value;
	}
}