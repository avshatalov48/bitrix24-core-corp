<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command;

interface CommandInterface
{
	public function toArray(): array;
}
