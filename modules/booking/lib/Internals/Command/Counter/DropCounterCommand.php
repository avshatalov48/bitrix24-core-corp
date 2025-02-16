<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Counter;

use Bitrix\Booking\Internals\CounterDictionary;

class DropCounterCommand
{
	public function __construct(
		public readonly int $entityId,
		public readonly CounterDictionary $type,
	)
	{
	}
}
