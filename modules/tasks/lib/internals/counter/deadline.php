<?php

namespace Bitrix\Tasks\Internals\Counter;


use Bitrix\Tasks\Util\Type\DateTime;

class Deadline
{
	private const DEFAULT_DEADLINE_LIMIT = 86400;

	/**
	 * @return DateTime
	 */
	public static function getExpiredTime(): DateTime
	{
		return new DateTime();
	}

	/**
	 * @return DateTime
	 */
	public static function getExpiredSoonTime(): DateTime
	{
		return DateTime::createFromTimestamp(time() + self::getDeadlineTimeLimit());
	}

	/**
	 * @param bool $reCache
	 * @return int
	 */
	public static function getDeadlineTimeLimit($reCache = false): int
	{
		static $time;

		if (!$time || $reCache)
		{
			$time = \CUserOptions::GetOption(
				'tasks',
				'deadlineTimeLimit',
				self::DEFAULT_DEADLINE_LIMIT
			);
		}

		return $time;
	}

	/**
	 * @param $timeLimit
	 * @return int
	 */
	public static function setDeadlineTimeLimit($timeLimit): int
	{
		\CUserOptions::SetOption('tasks', 'deadlineTimeLimit', $timeLimit);
		return self::getDeadlineTimeLimit(true);
	}

	/**
	 * @param $deadline
	 * @return bool
	 */
	public static function isDeadlineExpired($deadline): bool
	{
		if (!$deadline || !($deadline = DateTime::createFrom($deadline)))
		{
			return false;
		}

		return $deadline->checkLT(self::getExpiredTime());
	}

	/**
	 * @param $deadline
	 * @return bool
	 */
	public static function isDeadlineExpiredSoon($deadline): bool
	{
		if (!$deadline || !($deadline = DateTime::createFrom($deadline)))
		{
			return false;
		}

		return $deadline->checkGT(self::getExpiredTime()) && $deadline->checkLT(self::getExpiredSoonTime());
	}
}