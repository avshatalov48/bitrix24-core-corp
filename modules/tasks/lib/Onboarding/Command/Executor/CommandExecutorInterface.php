<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command\Executor;

use Bitrix\Tasks\Onboarding\Command\CommandCollection;
use Bitrix\Tasks\Onboarding\Command\CommandInterface;
use Bitrix\Tasks\Onboarding\Command\CountableCommandInterface;
use Bitrix\Tasks\Onboarding\Command\Result\BatchCommandResult;

interface CommandExecutorInterface
{
	public function __invoke(CommandInterface $command, BatchCommandResult $result, ?CommandCollection $commands = null): void;
}