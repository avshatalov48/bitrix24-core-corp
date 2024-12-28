<?php

namespace Bitrix\Crm\Service\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class StackLogger implements LoggerInterface
{
	use LoggerTrait;

	/** @var LoggerInterface[] */
	private array $loggers;
	public function __construct(\Psr\Log\LoggerInterface ...$loggers)
	{
		$this->loggers = $loggers;
	}

	public function log($level, \Stringable|string $message, array $context = []): void
	{
		foreach ($this->loggers as $logger)
		{
			$logger->log($level, $message, $context);
		}
	}
}
