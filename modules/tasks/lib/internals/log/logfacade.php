<?php

namespace Bitrix\Tasks\Internals\Log;

use Throwable;

final class LogFacade
{
	private static ?Log $logger = null;

	public static function log($data): void
	{
		self::getLogger()->collect($data);
	}

	public static function logThrowable(Throwable $throwable): void
	{
		self::getLogger()->collect($throwable->getMessage());
	}

	private static function getLogger(): Log
	{
		if (is_null(self::$logger))
		{
			self::$logger = new Log();
		}

		return self::$logger;
	}
}