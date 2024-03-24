<?php

namespace Bitrix\Tasks\Internals\Log;

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
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

	public static function logErrors(ErrorCollection $errors): void
	{
		foreach ($errors as $error)
		{
			self::logError($error);
		}
	}

	public static function logError(Error $error): void
	{
		self::getLogger()->collect($error->getMessage());
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