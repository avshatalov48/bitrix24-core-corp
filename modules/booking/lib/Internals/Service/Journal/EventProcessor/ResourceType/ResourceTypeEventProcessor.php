<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Journal\EventProcessor\ResourceType;

use Bitrix\Booking\Command\ResourceType\AddResourceTypeCommand;
use Bitrix\Booking\Command\ResourceType\RemoveResourceTypeCommand;
use Bitrix\Booking\Command\ResourceType\UpdateResourceTypeCommand;
use Bitrix\Booking\Internals\Service\Enum\EventType;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\EventProcessor;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalEventCollection;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Main\Event;

class ResourceTypeEventProcessor implements EventProcessor
{
    public function process(JournalEventCollection $eventCollection): void
    {
        foreach ($eventCollection as $event)
        {
            match ($event->type)
            {
                JournalType::ResourceTypeAdded => $this->processResourceTypeAddedEvent($event),
                JournalType::ResourceTypeUpdated => $this->processResourceTypeUpdatedEvent($event),
                JournalType::ResourceTypeDeleted => $this->processResourceTypeDeletedEvent($event),
                default => '',
            };
        }
    }

    public function processResourceTypeAddedEvent(JournalEvent $event): void
    {
        $command = AddResourceTypeCommand::mapFromArray($event->data);

        $this->sendBitrixEvent(
            type: 'onResourceTypeAdd',
            parameters: ['resourceType' => $command->resourceType],
        );
    }

    public function processResourceTypeUpdatedEvent(JournalEvent $event): void
    {
        $command = UpdateResourceTypeCommand::mapFromArray($event->data);

        $this->sendBitrixEvent(
            type: 'onResourceTypeUpdate',
            parameters: ['resourceType' => $command->resourceType],
        );
    }

    public function processResourceTypeDeletedEvent(JournalEvent $event): void
    {
		$command = RemoveResourceTypeCommand::mapFromArray($event->data);

		$this->sendBitrixEvent(
			type: 'onResourceTypeDelete',
			parameters: ['resourceTypeId' => $command->id],
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
