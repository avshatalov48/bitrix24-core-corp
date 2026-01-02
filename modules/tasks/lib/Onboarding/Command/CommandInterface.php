<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command;

use Bitrix\Tasks\Onboarding\Command\Result\CommandResult;

interface CommandInterface
{
	public function getId(): int;
	public function getCode(): string;
	public function __invoke(): CommandResult;
}