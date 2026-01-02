<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command;

interface CommandInterface
{
	public function run(): mixed;
	public function runInBackground(): bool;
	public function runWithDelay(int $milliseconds): bool;
}
