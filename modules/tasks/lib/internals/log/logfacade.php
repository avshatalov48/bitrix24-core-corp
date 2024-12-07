<?php

namespace Bitrix\Tasks\Internals\Log;

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Throwable;

final class LogFacade
{
	private static array $loggers = [];

	public static function log(mixed $data, string $marker = Log::DEFAULT_MARKER): void
	{
		self::getLogger($marker)->collect($data);
	}

	public static function logThrowable(Throwable $throwable, string $marker = Log::DEFAULT_MARKER): void
	{
		self::getLogger($marker)->collect([
			'message' => $throwable->getMessage(),
			'file' => $throwable->getFile(),
			'line' => $throwable->getLine(),
			'backtrace' => $throwable->getTraceAsString(),
		]);
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

	private static function getLogger(string $marker = Log::DEFAULT_MARKER): Log
	{
		if (!isset(self::$loggers[$marker]))
		{
			self::$loggers[$marker] = new Log($marker);
		}

		return self::$loggers[$marker];
	}
}