<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Journal\EventProcessor\WaitListItem;

use Bitrix\Booking\Command\WaitListItem\AddWaitListItemCommand;
use Bitrix\Booking\Command\WaitListItem\UpdateWaitListItemCommand;
use Bitrix\Booking\Entity\WaitListItem\WaitListItem;
use Bitrix\Booking\Internals\Service\Enum\EventType;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\EventProcessor;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalEventCollection;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Main\Event;

class WaitListItemEventProcessor implements EventProcessor
{
	public function process(JournalEventCollection $eventCollection): void
	{
		/** @var JournalEvent $event */
		foreach ($eventCollection as $event)
		{
			match ($event->type)
			{
				JournalType::WaitListItemAdded => $this->processWaitListItemAddedEvent($event),
				JournalType::WaitListItemUpdated => $this->processWaitListItemUpdatedEvent($event),
				JournalType::WaitListItemDeleted => $this->processWaitListItemDeletedEvent($event),
				default => '',
			};
		}
	}

	public function processWaitListItemAddedEvent(JournalEvent $event): void
	{
		$command = AddWaitListItemCommand::mapFromArray($event->data);

		$this->sendBitrixEvent(
			type: 'onWaitListItemAdd',
			parameters: ['waitListItem' => $command->waitListItem],
		);
	}

	public function processWaitListItemUpdatedEvent(JournalEvent $event): void
	{
		$command = UpdateWaitListItemCommand::mapFromArray($event->data);

		$this->sendBitrixEvent(
			type: 'onWaitListItemUpdate',
			parameters: ['waitListItem' => $command->waitListItem],
		);
	}

	public function processWaitListItemDeletedEvent(JournalEvent $journalEvent): void
	{
		$this->sendBitrixEvent(
			type: 'onWaitListItemDelete',
			parameters:  [
				'waitListItem' => WaitListItem::mapFromArray($journalEvent->data['waitListItem']),
				'removedBy' => $journalEvent->data['removedBy'],
			],
		);
	}

	private function sendBitrixEvent(string $type, array $parameters): void
	{
		(new Event(
			moduleId: 'booking',
			type: $type,
			parameters: $parameters,
		))->send();
	}
}
