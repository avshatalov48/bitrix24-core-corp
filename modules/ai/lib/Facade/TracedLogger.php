<?php

declare(strict_types=1);

namespace Bitrix\AI\Facade;

use Bitrix\Main\Diag;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Closure;
use Stringable;

class TracedLogger
{
	private ?LoggerInterface $logger;

	public function __construct()
	{
		$this->logger = Diag\Logger::create('ai.TracedLogger');
	}

	public function emergency(Stringable|string $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::EMERGENCY, $message, $context);
	}

	public function alert(Stringable|string $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::ALERT, $message, $context);
	}

	public function critical(Stringable|string $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	public function error(Stringable|string $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::ERROR, $message, $context);
	}

	public function warning(string|Stringable $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::WARNING, $message, $context);
	}

	public function notice(Stringable|string $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::NOTICE, $message, $context);
	}

	public function info(string|Stringable $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::INFO, $message, $context);
	}

	public function debug(string|Stringable $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::DEBUG, $message, $context);
	}

	private function log($level, Stringable|string $message, array|Closure $context = []): void
	{
		$message = $this->addMessageMetadata($message);
		$context['logLevel'] = strtoupper($level);
		$this->logger?->log($level, $message, $this->prepareContext($context));
	}

	private function prepareContext(array|Closure $context): array
	{
		if ($context instanceof Closure)
		{
			$context = $context();
		}

		return $context;
	}

	/**
	 * @param Stringable|string $message
	 * @return string
	 */
	private function addMessageMetadata(Stringable|string $message): string
	{
		return "{date} {host} [{trace-id}] {logLevel}: $message\n";
	}
}
