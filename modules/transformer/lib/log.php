<?php

namespace Bitrix\Transformer;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Diag\FileLogger;
use Bitrix\Main\Diag\Logger;
use Bitrix\Main\IO\Path;
use Bitrix\Main\ModuleManager;
use Bitrix\Transformer\Log\AddMessage2LogLogger;
use Bitrix\Transformer\Log\JsonLogFormatter;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class Log
{
	const LOG = '/bitrix/modules/transformer.log';
	private const LOGGER_ID = 'transformer.Default';

	private static function getMode(): bool
	{
		if (Option::get('transformer', 'debug'))
		{
			return true;
		}
		return false;
	}

	final public static function logger(): LoggerInterface
	{
		// maybe there is a custom logger in .settings.php
		$logger = Logger::create(self::LOGGER_ID);
		if ($logger)
		{
			return $logger;
		}

		$globalContext = [
			'loggerId' => self::LOGGER_ID,
		];

		// default logger
		if (ModuleManager::isModuleInstalled('bitrix24'))
		{
			$logger = new AddMessage2LogLogger();

			$logger->setLevel(Option::get('transformer', 'log_level', LogLevel::ERROR));
			$logger->setFormatter(
				new JsonLogFormatter(globalContext: $globalContext),
			);
		}
		else
		{
			// logs are disabled
			if (!self::getMode())
			{
				return new NullLogger();
			}

			$logger = new FileLogger(
				Path::combine(Application::getDocumentRoot(), self::LOG),
				0, // dont rotate logs by default
			);

			$logger->setLevel(Option::get('transformer', 'log_level', LogLevel::DEBUG));
			$logger->setFormatter(
				new JsonLogFormatter(
					lineBreakAfterEachMessage: true,
					globalContext: $globalContext,
				),
			);
		}

		return $logger;
	}

	/**
	 * @deprecated Use Log::logger() instead
	 *
	 * @param string|array $str Record to write.
	 * @return void
	 */
	public static function write($str): void
	{
		if (is_array($str))
		{
			$str = print_r($str, 1);
		}

		self::logger()->debug($str);
	}

	/**
	 * clears log file.
	 * @return void
	 */
	public static function clear(): void
	{
		if (self::getMode())
		{
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].self::LOG, '');
		}
	}
}
