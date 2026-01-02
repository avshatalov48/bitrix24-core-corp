<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Trait;

use Bitrix\Main\Event;

trait SendEventTrait
{
	private function sendEvent(string $type, array $parameters): void
	{
		(new Event(
			moduleId: 'booking',
			type: $type,
			parameters: $parameters,
		))->send();
	}
}
