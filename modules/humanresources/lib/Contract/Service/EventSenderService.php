<?php

namespace Bitrix\HumanResources\Contract\Service;

use Bitrix\HumanResources\Contract\Enum\EventName;
use Bitrix\Main\Event;

interface EventSenderService
{
	public function send(EventName $event, array $eventData): Event;

	public function removeEventHandlers(string $fromModuleId, string $event): void;

	/**
	 * Create an Event and store it in queue
	 *
	 * @param EventName $event
	 * @param array $eventData
	 * @return Event
	 */
	public function queueEvent(EventName $event, array $eventData): Event;

	/**
	 * Send every Event stored in queue
	 *
	 * @return void
	 */
	public function sendQueued(): void;

	/**
	 * Clear Event queue
	 *
	 * @return void
	 */
	public function clearQueue(): void;
}