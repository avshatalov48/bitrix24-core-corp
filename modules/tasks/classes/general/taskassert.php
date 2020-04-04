<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

class CTaskAssertException extends \Bitrix\Main\ArgumentException
{
}


class CTaskAssert
{
	// Error log levels:
	const ELL_TRACE   = 0x001;	// track control flow
	const ELL_DEBUG   = 0x002;	// track conditions
	const ELL_INFO    = 0x004;	// key points of normal control flow
	const ELL_WARN    = 0x008;	// synonym for ELL_WARNING
	const ELL_WARNING = 0x010;	// non-critical erros, but expected correct result
	const ELL_ERROR   = 0x020;	// errors, incorrect results expected
	const ELL_FATAL   = 0x040;

	private static $bLogEnabled = false;
	private static $pathToLogFile = false;
	private static $fp = null;	// file pointer

	// List of error log levels, that will be logged
	private static $bmEnabledLogLevels = array();

	private static $sessId = false;


	/**
	 * @throws CTaskAssertException when assertion failed
	 */
	public static function assert($assertion)
	{
		if ($assertion === true)
			return;

		if (self::$bLogEnabled !== false)
			static::log("Assertion failed!", self::ELL_ERROR, true);

		throw new CTaskAssertException();
	}


	public static function logWarning($logMessage)
	{
		trigger_error('CTaskAssert::ELL_WARNING (no data loss expected): ' . $logMessage, E_USER_WARNING);
		self::log($logMessage, self::ELL_WARNING);
		return (false);
	}


	public static function logError($logMessage)
	{
		trigger_error('CTaskAssert::ELL_ERROR (data loss or corruption expected): ' . $logMessage, E_USER_WARNING);
		self::log($logMessage, self::ELL_ERROR);
		return (false);
	}


	public static function logFatal($logMessage)
	{
		trigger_error('CTaskAssert::ELL_FATAL: ' . $logMessage, E_USER_WARNING);
		self::log($logMessage, self::ELL_FATAL);
		return (false);
	}


	public static function log($logMessage, $errLogLevel = self::ELL_TRACE, $showBacktrace = false)
	{
		if (self::$bLogEnabled === false)
			return;

		// Log only selected errorlevels
		if ( ! ($errLogLevel & self::$bmEnabledLogLevels) )
			return;

		/** @noinspection PhpUnusedLocalVariableInspection */
		$prefix = '';

		switch ($errLogLevel)
		{
			case self::ELL_TRACE:
				$prefix = '[TRACE] ';
			break;

			case self::ELL_DEBUG:
				$prefix = '[DEBUG] ';
			break;

			case self::ELL_INFO:
				$prefix = '[INFO] ';
			break;

			case self::ELL_WARNING:
				$prefix = '[WARN] ';
			break;

			case self::ELL_ERROR:
				$prefix = '[ERROR] ';
			break;

			case self::ELL_FATAL:
				$prefix = '[FATAL] ';
			break;

			default:
				$prefix = '[UNKNOWN ERROR LOG LEVEL #' . $errLogLevel . '] ';
			break;
		}

		list($usec, $sec) = explode(" ", microtime());
		$strMessage = date('Y-m-d H:i:s', $sec) . '.' . substr($usec, 2, 4)
			. ' ' . self::$sessId . ' ' . $prefix . $logMessage . "\n";

		if ($showBacktrace && function_exists('debug_backtrace'))
		{
			$strMessage .= "\nStack trace:, referer: " 
				. $_SERVER['REQUEST_METHOD'] . ' '
				. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "\n";
			$arBacktraces = debug_backtrace();

			$iMx = count($arBacktraces);

			$ii = 0;
			for ($i = $iMx - 1; $i >= 0; $i--)
			{
				$arBacktrace = $arBacktraces[$i];

				$strMessage .= str_pad(++$ii, 2, ' ', STR_PAD_LEFT) . '. ';

				if ($arBacktrace['type'] != '')
					$strMessage .= $arBacktrace['class'] . $arBacktrace['type'];

				$strMessage .= $arBacktrace['function'] . '(';

				if (
					($arBacktrace['function'] !== 'IncludeComponent')
					&& ($arBacktrace['function'] !== 'includeComponent')
					&& ($arBacktrace['function'] !== 'run')
					&& ($arBacktrace['function'] !== 'runTest')
					&& ($arBacktrace['function'] !== 'invokeArgs')
				)
				{
					$arArgsFormatted = array();

					foreach ($arBacktrace['args'] as $arg)
						$arArgsFormatted[] = var_export($arg, true);

					$strMessage .=  implode(', ', $arArgsFormatted);
				}

				$strMessage .= ') ' . $arBacktrace['file'] 
					. ':' . $arBacktrace['line'] . "\n";
			}
		}

		// do log
		if (self::$fp !== null)
		{
			@fwrite(
				self::$fp,
				$strMessage
			);
		}
	}


	public static function setLogFileName($fullPathToLogFile)
	{
		// Open and closes files here, if need
		if ($fullPathToLogFile !== self::$pathToLogFile)
		{
			self::$pathToLogFile = $fullPathToLogFile;

			if (is_resource(self::$fp))
			{
				@fclose(self::$fp);
				self::$fp = null;	// file closed
			}

			$fp = @fopen($fullPathToLogFile, 'ab+');

			if ( ! is_resource($fp) )
			{
				AddMessage2Log('[ERROR] CTaskAssert: cannot open/create log file with name: ' . $fullPathToLogFile, 'tasks');
				return (false);
			}

			self::$fp = $fp;
		}

		return (true);
	}


	public static function disableLogging()
	{
		if (self::$bLogEnabled !== true)
			return;		// nothing to do

		static::log('CTaskAssert: stop logging.', self::ELL_INFO);
		self::$bLogEnabled = false;
		self::$bmEnabledLogLevels = 0;
	}


	/**
	 * @param integer $enableLogLevels bitmask of enabled log levels. By default all error levels will be enabled.
	 *
	 * @return bool
	 * @example CTaskAssert::enableLogging(CTaskAssert::ELL_TRACE | CTaskAssert::ELL_DEBUG);
	 */
	public static function enableLogging($enableLogLevels = null)
	{
		if (self::$sessId === false)
		{
			$tmp = '';

			for ($i=0; $i < 4; $i++)
			{
				if (mt_rand(1, 36) <= 10)
					$tmp .= chr(mt_rand(48,57));
				else
					$tmp .= chr(mt_rand(65,90));
			}

			self::$sessId = $tmp;
			unset ($tmp);
		}

		if ((self::$fp === null) && (self::$pathToLogFile === false))
			self::setLogFileName($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/tasks.log');

		if (self::$fp === null)
		{
			AddMessage2Log('[ERROR] CTaskAssert::enableLogging(): Log file cannot be used or not set. Logging not enabled.', 'tasks');
			return (false);
		}

		// if wrong error levels given, log this and switch to default value
		if (($enableLogLevels !== null) && ( ! is_int($enableLogLevels) ))
		{
			$enableLogLevels = null;
			AddMessage2Log('[ERROR] CTaskAssert::enableLogging(): Invalid log errors level set, using defaults', 'tasks');
		}

		// If null given, than use default value (all levels enabled)
		if ($enableLogLevels === null)
		{
			$enableLogLevels = self::ELL_TRACE | self::ELL_DEBUG 
				| self::ELL_INFO | self::ELL_WARNING | self::ELL_ERROR
				| self::ELL_FATAL;
		}

		self::$bmEnabledLogLevels = $enableLogLevels;

		if ($enableLogLevels !== 0)
			self::$bLogEnabled = true;
		else
			self::$bLogEnabled = false;

		return (true);
	}


	public static function isLaxIntegers()
	{
		return (static::_isLaxIntegers(func_get_args()));
	}


	public static function assertLaxIntegers()
	{
		static::assert(static::_isLaxIntegers(func_get_args()));
	}


	private static function _isLaxIntegers($args)
	{
		if (count($args) == 0)
			return (false);

		foreach ($args as $value)
		{
			if ( ! (
				is_int($value)
				|| (
					is_string($value)
					&& ($value !== '')
					&& (
						preg_match('/^[0-9]{1,}$/', $value)		// positive integer
						|| (		// or negative integer
							(substr($value, 0, 1) === '-')
							&& preg_match('/^[0-9]{1,}$/', substr($value, 1))
						)
					)
				)
			))
			{
				return (false);
			}
		}

		return (true);
	}
}
