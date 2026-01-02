<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command;

interface CountableCommandInterface extends CommandInterface
{
	public function getExecutionLimit(): int;
	public function canIncreaseCounter(): bool;
}