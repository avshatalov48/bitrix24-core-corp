<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command\Executor;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Tasks\Onboarding\Command\CommandCollection;
use Bitrix\Tasks\Onboarding\Command\CommandInterface;
use Bitrix\Tasks\Onboarding\Command\CountableCommandInterface;
use Bitrix\Tasks\Onboarding\Command\Result\BatchCommandResult;

class BatchCommandExecutor
{
	public function execute(CommandCollection $commands): BatchCommandResult
	{
		$result = new BatchCommandResult();

		foreach ($commands as $command)
		{
			$executor = $this->getCommandExecutor($command);

			$executor($command, $result, $commands);
		}

		return $result;
	}

	private function getCommandExecutor(CommandInterface $command): CommandExecutorInterface
	{
		return match (true)
		{
			$command instanceof CountableCommandInterface => new CountableCommandExecutor(),
			default => new CommandExecutor(),
		};
	}
}