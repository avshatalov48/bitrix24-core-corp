<?php

declare(strict_types=1);

namespace Bitrix\Baas\Internal\Diag;

use Bitrix\Main\Diag;
use Closure;
use Bitrix\Baas;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class Logger implements LoggerInterface
{
	use Baas\Internal\Trait\SingletonConstructor;

	private ?Diag\Logger $logger = null;

	protected function __construct(
		private Baas\Config\Client $config,
	)
	{
		if (
			$this->config->isLoggingEnabled()
			&& ($this->logger = Diag\Logger::create('baas.baseLogger'))
		)
		{
			$this->logger->setFormatter(new LogFormatterJson(
				lineBreakAfterEachMessage: true,
			));
		}
	}

	public function emergency(\Stringable|string $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::EMERGENCY, $message, $context);
	}

	public function alert(\Stringable|string $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::ALERT, $message, $context);
	}

	public function critical(\Stringable|string $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	public function error(\Stringable|string $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::ERROR, $message, $context);
	}

	public function warning(string|\Stringable $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::WARNING, $message, $context);
	}

	public function notice(\Stringable|string $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::NOTICE, $message, $context);
	}

	public function info(string|\Stringable $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::INFO, $message, $context);
	}

	public function debug(string|\Stringable $message, array|Closure $context = []): void
	{
		$this->log(LogLevel::DEBUG, $message, $context);
	}

	public function log($level, \Stringable|string $message, array|Closure $context = []): void
	{
		if (isset($this->logger))
		{
			$this->logger->log($level, $message, $this->prepareContext($context));
		}
	}

	private function prepareContext(array|Closure $context): array
	{
		if ($context instanceof Closure)
		{
			$context = $context();
		}

		return $context;
	}

	public static function getInstance(): static
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self(
				new Baas\Config\Client(),
			);
		}

		return self::$instance;
	}
}
