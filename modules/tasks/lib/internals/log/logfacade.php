<?php

namespace Bitrix\Tasks\Internals\Log;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\SystemException;
use Bitrix\Main\Validation\ValidationError;
use ReflectionClass;
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

	public static function logValidationErrors(ErrorCollection $errors): void
	{
		if (!self::isDevMode() && Option::get('tasks', 'tasks_log_validation_errors', 'N') !== 'Y')
		{
			return;
		}

		$messages = [];
		foreach ($errors as $error)
		{
			if ($error instanceof ValidationError)
			{
				$messages [] =
					$error->getCode() . ':' . $error->getMessage() . ':' . $error->getFailedValidator()::class;
			}
		}

		if (!empty($messages))
		{
			self::log($messages);
		}
	}

	public static function handle(string|Throwable|Error $error, string $wrapperClass = SystemException::class): void
	{
		$exceptionHandler = Application::getInstance()->getExceptionHandler();

		if ($error instanceof Throwable)
		{
			$exceptionHandler->writeToLog($error);

			return;
		}

		$errorMessage = match (true)
		{
			$error instanceof Error => $error->getMessage(),
			is_string($error) => $error,
		};

		$reflector = new ReflectionClass($wrapperClass);

		if (!$reflector->isInstantiable() || !$reflector->isSubclassOf(Throwable::class))
		{
			return;
		}

		$exception = $reflector->newInstance($errorMessage);

		$exceptionHandler->writeToLog($exception);
	}

	public static function logWarn(string $message, int $level = E_USER_WARNING): void
	{
		if (self::isDevMode() || Option::get('tasks', 'tasks_log_warnings', 'N') === 'Y')
		{
			trigger_error($message, $level);
		}
	}

	private static function getLogger(string $marker = Log::DEFAULT_MARKER): Log
	{
		if (!isset(self::$loggers[$marker]))
		{
			self::$loggers[$marker] = new Log($marker);
		}

		return self::$loggers[$marker];
	}

	private static function isDevMode(): bool
	{
		$exceptionHandling = Configuration::getValue('exception_handling');

		return !empty($exceptionHandling['debug']);
	}
}
