<?php

namespace Bitrix\Call\Logger;

use Bitrix\Main\Diag;
use Bitrix\Main\Diag\FileLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;


class Logger extends FileLogger
{
	public const LOG_ID = 'call.logger';

	private const LOG_MAX_SIZE = 1073741824; //1Gb

	/**
	 * @var Diag\Logger[]
	 */
	private static array $instance = [];

	/** @var string */
	private static string $sessionId = '';


	public static function getInstance(?string $logId = null): LoggerInterface
	{
		if (!\Bitrix\Call\Integration\AI\CallAISettings::isLoggingEnable())
		{
			return new NullLogger();
		}

		if (!$logId)
		{
			$logId = self::LOG_ID;
		}

		if (empty(self::$sessionId))
		{
			self::$sessionId = uniqid();
		}

		if (!isset(self::$instance[$logId]))
		{
			self::$instance[$logId] =
				Diag\Logger::create($logId)
				?? new self(self::getLogPath(), self::getLogMaxSize())
			;
		}

		self::$instance[$logId]->getFormatter();

		return self::$instance[$logId];
	}

	private static function getLogPath(): string
	{
		$fileLog = \Bitrix\Main\Config\Option::get('call', 'call_log_file', '');
		if (!empty($fileLog))
		{
			return $fileLog;
		}
		if (isset($_SERVER['DOCUMENT_ROOT']))
		{
			return $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/call.log';
		}
		return '';
	}

	private static function getLogMaxSize(): int
	{
		return self::LOG_MAX_SIZE;
	}

	/**
	 * Sets current log session id.
	 * @param string $sessionId
	 * @return void
	 */
	public static final function setSessionId(string $sessionId): void
	{
		self::$sessionId = $sessionId;
	}

	/**
	 * Returns current log session id.
	 * @return string
	 */
	public static final function getSessionId(): string
	{
		return self::$sessionId;
	}

	/**
	 * @inheritDoc
	 */
	public function log($level, string|\Stringable $message, array $context = []): void
	{
		$context['sessionId'] = self::getSessionId();

		$fullMessage = "{date}; {sessionId}; {$level}; {host}";

		$fullMessage .= "; {$message}";

		if (isset($context['payload']))
		{
			$fullMessage .= "; payload: ". \json_encode($context['payload']);
		}
		if (isset($context['trace']))
		{
			$fullMessage .= "\n{trace}";
		}
		if (isset($context['exception']))
		{
			$fullMessage .= "\n{exception}";
		}

		$fullMessage .= "\n";

		parent::log($level, $fullMessage, $context);
	}
}