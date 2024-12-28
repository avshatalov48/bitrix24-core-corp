<?php

namespace Bitrix\HumanResources\Contract\Service;

use Bitrix\HumanResources\Contract\Enum\EventName;
use Bitrix\Main\Event;

interface EventSenderService
{
	public function send(EventName $event, array $eventData): Event;

	public function removeEventHandlers(string $fromModuleId, string $event): void;
}