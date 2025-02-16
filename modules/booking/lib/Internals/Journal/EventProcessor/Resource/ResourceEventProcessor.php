<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Journal\EventProcessor\Resource;

use Bitrix\Booking\Internals\Command\Resource;
use Bitrix\Booking\Internals\Journal\EventProcessor\EventProcessor;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalEventCollection;
use Bitrix\Booking\Internals\Journal\JournalType;

class ResourceEventProcessor implements EventProcessor
{
	public function process(JournalEventCollection $eventCollection): void
	{
		/** @var JournalEvent $event */
		foreach ($eventCollection as $event)
		{
			match ($event->type)
			{
				JournalType::ResourceAdded => $this->processResourceAddedEvent($event),
				default => '',
			};
		}
	}

	private function processResourceAddedEvent(JournalEvent $event): void
	{
		// event -> command
		$command = Resource\AddCommand::mapFromArray($event->data);

		$this->addResourceCopies($command, $event);
	}

	private function addResourceCopies(Resource\AddCommand $command, JournalEvent $event): void
	{
		$copies = $command->getCopies();

		if ($copies && $copies > 0)
		{
			\Bitrix\Main\Update\Stepper::bindClass(
				className: ResourceCopierStepper::class,
				moduleId: ResourceCopierStepper::MODULE,
				delay: 1,
				withArguments: [$event->id],
			);
		}
	}
}
