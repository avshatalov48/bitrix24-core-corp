<?php
namespace Bitrix\Sign\Debug;

use Bitrix\Main\Diag;
use Psr\Log;
use Stringable;

class Logger extends Diag\Logger implements Log\LoggerAwareInterface
{
	use Log\LoggerAwareTrait;

	private const INTERNAL_LOGGER_ID = 'SignDebugLogger';

	private ?string $host;

	public static function getInstance(string $host = null): self
	{
		static $instance;
		if ($instance === null)
		{
			if (is_null($host))
			{
				$context = \Bitrix\Main\Context::getCurrent();
				$host = $context ? $context->getServer()->getHttpHost() : null;
			}

			$logger = Diag\Logger::create(self::INTERNAL_LOGGER_ID, [$host]);
			if ($logger)
			{
				$logger->setFormatter(new LogFormatter());
			}

			$instance = new self($logger, $host);
		}
		return $instance;
	}

	protected function __construct(?Log\LoggerInterface $logger, ?string $host)
	{
		if ($logger)
		{
			$this->setLogger($logger);
		}
		$this->host = $host;
	}

	public function trace(string|\Stringable $message = ''): void
	{
		$this->debug($message, [
			LogFormatter::PLACEHOLDER_TRACE => debug_backtrace(),
		]);
	}

	public function dump(mixed $dump, string|\Stringable $message = ''): void
	{
		$this->debug($message, [
			LogFormatter::SIGN_PLACEHOLDER_DUMP => $dump,
		]);
	}

	public function log($level, string|\Stringable $message, array $context = []): void
	{
		if ($logger = $this->getInternalLogger())
		{
			$context[LogFormatter::SIGN_PLACEHOLDER_HOST] = $this->host;
			$logger->log($level, $message, $context);
		}
	}

	protected function logMessage(string $level, string $message) {}

	private function getInternalLogger(): ?Log\LoggerInterface
	{
		return $this->logger;
	}
}
