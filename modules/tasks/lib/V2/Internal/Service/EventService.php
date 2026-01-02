<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service;

use Bitrix\Main\Event;

class EventService
{
	public function send(string $type, array $parameters = []): void
	{
		$event = new Event('tasks', $type, $parameters);

		$event->send();
	}
}