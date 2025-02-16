<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Journal\EventProcessor\PushPull;

use Bitrix\Booking\Integration\Pull\PushEvent;
use Bitrix\Booking\Integration\Pull\PushService;
use Bitrix\Booking\Internals\Journal\EventProcessor\EventProcessor;
use Bitrix\Booking\Internals\Journal\JournalEventCollection;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalType;

class PushPullEventProcessor implements EventProcessor
{
	public function process(JournalEventCollection $eventCollection): void
	{
		foreach ($eventCollection as $event)
		{
			$this->processEvent($event);
		}
	}

	private function processEvent(JournalEvent $event): void
	{
		$commandType = $this->getCommandForEventType($event->type);

		if ($commandType !== null)
		{
			(new PushService())->sendEvent(
				new PushEvent(
					command: $commandType->value,
					tag: $commandType->getTag(),
					params: $event->data,
					entityId: $event->entityId,
				)
			);
		}
	}

	private function getCommandForEventType(JournalType $type): ?PushPullCommandType
	{
		return match ($type)
		{
			JournalType::BookingAdded => PushPullCommandType::BookingAdded,
			JournalType::BookingUpdated, JournalType::BookingConfirmed => PushPullCommandType::BookingUpdated,
			JournalType::BookingClientsUpdated => PushPullCommandType::BookingClientUpdated,
			JournalType::BookingDeleted, JournalType::BookingCanceled => PushPullCommandType::BookingDeleted,
			JournalType::ResourceAdded => PushPullCommandType::ResourceAdded,
			JournalType::ResourceUpdated => PushPullCommandType::ResourceUpdated,
			JournalType::ResourceDeleted => PushPullCommandType::ResourceDeleted,
			JournalType::ResourceTypeAdded => PushPullCommandType::ResourceTypeAdded,
			JournalType::ResourceTypeUpdated => PushPullCommandType::ResourceTypeUpdated,
			JournalType::ResourceTypeDeleted => PushPullCommandType::ResourceTypeDeleted,
			default => null,
		};
	}
}
