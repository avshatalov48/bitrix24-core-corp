<?php

namespace Bitrix\Crm\Ml\Internals;

class Lock
{
	protected static $lockFiles = [];

	/**
	 * Gets named lock. Returns true if lock was successful and false otherwise.
	 *
	 * @param string $lockName Name of the lock.
	 * @return bool
	 */
	public static function get($lockName)
	{
		$hash = md5($lockName);
		$lockFileName = \CTempFile::GetAbsoluteRoot()."/crm-ml-$hash.lock";
		CheckDirPath($lockFileName);
		static::$lockFiles[$lockName] = fopen($lockFileName, "w");
		if (!is_resource(static::$lockFiles[$lockName]))
		{
			static::$lockFiles[$lockName] = null;
			return false;
		}
		$locked = flock(static::$lockFiles[$lockName], LOCK_EX | LOCK_NB);
		if (!$locked)
		{
			fclose(static::$lockFiles[$lockName]);
			static::$lockFiles[$lockName] = null;
			return false;
		}

		return true;
	}

	public static function release($lockName)
	{
		if (is_resource(static::$lockFiles[$lockName]))
		{
			flock(static::$lockFiles[$lockName], LOCK_UN);
			fclose(static::$lockFiles[$lockName]);
			static::$lockFiles[$lockName] = null;
		}
	}
}