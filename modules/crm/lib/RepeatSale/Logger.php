<?php

declare(strict_types=1);

namespace Bitrix\Crm\RepeatSale;

use Bitrix\Crm\Service\Container;
use Psr\Log\LoggerInterface;

final class Logger
{
	private LoggerInterface $logger;
	private string $messagePrefix;

	public const LOG_MARK_AI_AUTO_LAUNCH = 'run_auto';
	public const LOG_MARK_AI_MANUAL_LAUNCH = 'run_manual';
	public const LOG_MARK_AI_PAID = 'paid_yes';
	public const LOG_MARK_AI_NOT_PAID = 'paid_no';

	public function __construct(string $messagePrefix = 'RepeatSale')
	{
		$this->logger = Container::getInstance()->getLogger('RepeatSale');
		$this->messagePrefix = $messagePrefix;
	}

	public function info(string $message, array $context): void
	{
		$this->logger->info($this->getPreparedMessage($message), $context);
	}

	public function debug(string $message, array $context): void
	{
		$this->logger->debug($this->getPreparedMessage($message), $context);
	}

	public function error(string $message, array $context): void
	{
		$this->logger->error($this->getPreparedMessage($message), $context);
	}

	protected function getPreparedMessage(string $message): string
	{
		if (empty($this->messagePrefix))
		{
			return $message;
		}

		return $this->messagePrefix . ': ' . $message;
	}
}
