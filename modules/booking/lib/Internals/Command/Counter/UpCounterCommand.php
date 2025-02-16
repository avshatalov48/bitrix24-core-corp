<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Counter;

use Bitrix\Booking\Internals\CounterDictionary;

class UpCounterCommand
{
	public function __construct(
		public readonly int $entityId,
		public readonly CounterDictionary $type,
	)
	{
	}
}
