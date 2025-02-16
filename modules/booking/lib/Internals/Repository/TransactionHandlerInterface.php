<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

interface TransactionHandlerInterface
{
	public function handle(callable $fn, string $errType): mixed;
}
