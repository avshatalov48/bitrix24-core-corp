<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command\Executor;

use Bitrix\Tasks\Onboarding\Command\CommandCollection;
use Bitrix\Tasks\Onboarding\Command\CommandInterface;
use Bitrix\Tasks\Onboarding\Command\CountableCommandInterface;
use Bitrix\Tasks\Onboarding\Command\Result\BatchCommandResult;

class CommandExecutor implements CommandExecutorInterface
{
	public function __invoke(CommandInterface $command, BatchCommandResult $result, ?CommandCollection $commands = null): void
	{
		$commandResult = $command();

		if ($commandResult->isSuccess() || !$commandResult->isRetryAllowed())
		{
			$result->addCompletedCommandId($command->getId());
		}
		else
		{
			$result->addNotCompletedCommandId($command->getId());

			$result->addErrors($commandResult->getErrors());
		}
	}
}