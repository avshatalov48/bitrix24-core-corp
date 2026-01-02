<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command\Executor;

use Bitrix\Tasks\Onboarding\Command\CommandCollection;
use Bitrix\Tasks\Onboarding\Command\CommandInterface;
use Bitrix\Tasks\Onboarding\Command\CountableCommandInterface;
use Bitrix\Tasks\Onboarding\Command\Result\BatchCommandResult;
use Bitrix\Tasks\Onboarding\Command\Trait\ContainerTrait;
use Bitrix\Tasks\Onboarding\DI\OnboardingContainer;
use Bitrix\Tasks\Onboarding\Internal\Factory\JobCodeFactory;

class CountableCommandExecutor implements CommandExecutorInterface
{
	use ContainerTrait;

	private OnboardingContainer $container;

	public function __invoke(CommandInterface $command, BatchCommandResult $result, CommandCollection|null $commands = null): void
	{
		if (!$command instanceof CountableCommandInterface)
		{
			return;
		}

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

		if (!$command->canIncreaseCounter())
		{
			return;
		}

		$this->increaseCount($command);

		if ($this->isLimitReached($command))
		{
			$commands?->removeByCode($command->getCode(), $command->getId());

			$result->addDuplicatedCommandCodes($command->getCode());
		}
	}

	private function increaseCount(CountableCommandInterface $command): void
	{
		$code = JobCodeFactory::createCodeByCommand($command);

		$this->getContainer()->getCounterService()->increment($code);
	}

	private function isLimitReached(CountableCommandInterface $command): bool
	{
		$currentCount = $this->getContainer()->getCounterRepository()->getByCode($command->getCode());

		return $currentCount >= $command->getExecutionLimit();
	}
}