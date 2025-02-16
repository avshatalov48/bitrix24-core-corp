<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Journal\EventProcessor\Counter;

use Bitrix\Booking\Internals\Command\Booking;
use Bitrix\Booking\Internals\Command\Counter\DropCounterCommand;
use Bitrix\Booking\Internals\Command\Counter\DropCounterCommandHandler;
use Bitrix\Booking\Internals\Command\Counter\UpCounterCommand;
use Bitrix\Booking\Internals\Command\Counter\UpCounterCommandHandler;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\CounterDictionary;
use Bitrix\Booking\Internals\Journal\EventProcessor\EventProcessor;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalEventCollection;
use Bitrix\Booking\Internals\Journal\JournalType;

class CounterEventProcessor implements EventProcessor
{
	public function process(JournalEventCollection $eventCollection): void
	{
		/** @var JournalEvent $event */
		foreach ($eventCollection as $event)
		{
			match ($event->type)
			{
				JournalType::BookingConfirmed => $this->processBookingConfirmed($event),
				JournalType::BookingUpdated => $this->processBookingUpdated($event),
				JournalType::BookingDeleted => $this->processBookingDeleted($event),
				JournalType::BookingDelayedNotificationInitialized => $this->processDelayedMessageInitialized($event),
				JournalType::BookingManagerConfirmNotificationSent => $this->processManagerConfirmMessageSent($event),
				default	=> '',
			};
		}
	}

	private function processBookingConfirmed(JournalEvent $event): void
	{
		$this->runDropCounterCommand($event->entityId, CounterDictionary::BookingDelayed);
		$this->runDropCounterCommand($event->entityId, CounterDictionary::BookingUnConfirmed);
	}

	private function processBookingUpdated(JournalEvent $event): void
	{
		$command = Booking\UpdateCommand::mapFromArray($event->data);
		$booking = $command->booking;
		$prevBooking = (isset($event->data['prevBooking']))
			? \Bitrix\Booking\Entity\Booking\Booking::mapFromArray($event->data['prevBooking'])
			: null
		;

		if ($prevBooking === null)
		{
			return;
		}

		$isVisitStatusKnown = $booking->isVisitStatusKnown();
		$isVisitStatusChanged = $isVisitStatusKnown && ($booking->getVisitStatus() !== $prevBooking->getVisitStatus());
		$isConfirmed = $booking->isConfirmed();
		$isConfirmStatusChanged = $isConfirmed && ($booking->isConfirmed() !== $prevBooking->isConfirmed());

		if ($isVisitStatusChanged || $isConfirmStatusChanged)
		{
			$this->runDropCounterCommand($event->entityId, CounterDictionary::BookingDelayed);
			$this->runDropCounterCommand($event->entityId, CounterDictionary::BookingUnConfirmed);
		}
	}

	private function processBookingDeleted(JournalEvent $event): void
	{
		$this->runDropCounterCommand($event->entityId, CounterDictionary::BookingDelayed);
		$this->runDropCounterCommand($event->entityId, CounterDictionary::BookingUnConfirmed);
	}

	private function processDelayedMessageInitialized(JournalEvent $event): void
	{
		$booking = Container::getBookingRepository()->getById($event->entityId);

		if ($booking === null)
		{
			return;
		}

		if ($booking->isVisitStatusKnown())
		{
			return;
		}

		$booking->setConfirmed(false);
		Container::getBookingRepository()->save($booking);

		$this->runUpCounterCommand($booking->getId(), CounterDictionary::BookingDelayed);
	}

	private function processManagerConfirmMessageSent(JournalEvent $event): void
	{
		$booking = Container::getBookingRepository()->getById($event->entityId);

		if ($booking === null)
		{
			return;
		}

		if ($booking->isConfirmed() || $booking->isVisitStatusKnown())
		{
			return;
		}

		$this->runUpCounterCommand($booking->getId(), CounterDictionary::BookingUnConfirmed);
	}

	private function runDropCounterCommand(int $entityId, CounterDictionary $type): void
	{
		(new DropCounterCommandHandler())(
			new DropCounterCommand(
				entityId: $entityId,
				type: $type,
			)
		);
	}

	private function runUpCounterCommand(int $entityId, CounterDictionary $type): void
	{
		(new UpCounterCommandHandler())(
			new UpCounterCommand(
				entityId: $entityId,
				type: $type,
			)
		);
	}
}
