<?php

namespace Bitrix\Transformer\Agent;

use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Type\DateTime;
use Bitrix\Transformer\Command;
use Bitrix\Transformer\Entity\CommandTable;
use Bitrix\Transformer\Service\Command\Locker;
use Bitrix\Transformer\Service\Integration\Analytics\Registrar;
use Bitrix\Transformer\Log;
use Psr\Log\LoggerInterface;

final class ExpiredCommandAgent
{
	private const AGENT_STRING = '\\' . self::class . '::run();';
	private const DEFAULT_LIMIT = 200;

	private LoggerInterface $logger;
	private Locker $locker;
	private Registrar $analyticsRegistrar;

	private function __construct()
	{
		$this->logger = Log::logger();
		$this->locker = ServiceLocator::getInstance()->get('transformer.service.command.locker');
		$this->analyticsRegistrar = ServiceLocator::getInstance()->get(
			'transformer.service.integration.analytics.registrar'
		);
	}

	public static function run(): string
	{
		$self = new self();
		$self->finishExpiredCommands();

		return self::AGENT_STRING;
	}

	private function finishExpiredCommands(): void
	{
		$queryResult = CommandTable::query()
			->setSelect(['*'])
			->whereIn('STATUS', [Command::STATUS_CREATE, Command::STATUS_SEND, Command::STATUS_UPLOAD])
			->where('DEADLINE', '<=', new DateTime())
			->addOrder('DEADLINE')
			->setLimit($this->getLimit())
			->exec()
		;

		while ($row = $queryResult->fetch())
		{
			$command = Command::initFromArray($row);

			$this->finishCommand($command);
		}
	}

	private function getLimit(): int
	{
		return (int)Option::get('transformer', 'expired_command_agent_limit', self::DEFAULT_LIMIT);
	}

	private function finishCommand(Command $command): void
	{
		if (!$this->locker->lock($command))
		{
			$this->logger->info(
				'{class}: could not acquire lock for command, seems that the result has arrived. Skipping.',
				['class' => __CLASS__, 'guid' => $command->getGuid()],
			);

			return;
		}

		$command->onDeadlineExceeded();

		$this->locker->unlock($command);

		$this->analyticsRegistrar->registerCommandFinish($command);
	}
}
