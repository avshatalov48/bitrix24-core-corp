<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command;

use Bitrix\Main\Type\Contract\Arrayable;

interface CommandInterface extends Arrayable
{
	public function run(): mixed;
	public function runInBackground(): bool;
	public function runWithDelay(int $milliseconds): bool;
}
