<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Journal\EventProcessor\Booking;

use Bitrix\Booking\Command\Booking\AddBookingCommand;
use Bitrix\Booking\Command\Booking\UpdateBookingCommand;
use Bitrix\Booking\Internals\Integration\Im\Chat;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\EventProcessor;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalEventCollection;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Service\Time;
use Bitrix\Main\Event;
use Bitrix\Booking\Entity;

class BookingEventProcessor implements EventProcessor
{
	public function process(JournalEventCollection $eventCollection): void
	{
		/** @var JournalEvent $event */
		foreach ($eventCollection as $event)
		{
			match ($event->type)
			{
				JournalType::BookingAdded => $this->processBookingAddedEvent($event),
				JournalType::BookingUpdated => $this->processBookingUpdatedEvent($event),
				JournalType::BookingDeleted => $this->processBookingDeletedEvent($event),
				JournalType::BookingCanceled => $this->processBookingCanceledEvent($event),
				default => '',
			};
		}
	}

	private function processBookingAddedEvent(JournalEvent $journalEvent): void
	{
		// event -> command
		$command = AddBookingCommand::mapFromArray($journalEvent->data);
		// set id for newly created booking
		$booking = $command->booking;
		$booking->setId($journalEvent->entityId);

		$this->sendBitrixEvent(type: 'onBookingAdd', parameters: ['booking' => $booking]);

		$hitsAt = $this->getHitsNeededAt($booking);
		if ($booking->getCreatedAt())
		{
			// 5 minutes after booking has been created
			$hitsAt[] = $booking->getCreatedAt() + Time::SECONDS_IN_MINUTE * 5;
		}

		if (!empty($hitsAt))
		{
			$this->sendBitrixEvent(type: 'onHitsNeeded', parameters: [
				'hitsAt' => $hitsAt,
			]);
		}
	}

	private function processBookingUpdatedEvent(JournalEvent $journalEvent): void
	{
		// event -> command
		$command = UpdateBookingCommand::mapFromArray($journalEvent->data);
		$updatedBooking = $command->booking;
		$prevBooking = !empty($journalEvent->data['prevBooking'])
			? Entity\Booking\Booking::mapFromArray($journalEvent->data['prevBooking'])
			: null
		;

		$this->sendBitrixEvent(type: 'onBookingUpdate', parameters: [
			'booking' => $updatedBooking,
			'prevBooking' => $prevBooking,
		]);

		$hitsAt = $this->getHitsNeededAt($updatedBooking);
		if (!empty($hitsAt))
		{
			$this->sendBitrixEvent(type: 'onHitsNeeded', parameters: [
				'hitsAt' => $hitsAt,
			]);
		}
	}

	private function processBookingDeletedEvent(JournalEvent $journalEvent): void
	{
		$this->sendBitrixEvent(type: 'onBookingDelete', parameters:  ['bookingId' => $journalEvent->entityId]);
	}

	private function sendBitrixEvent(string $type, array $parameters): void
	{
		(new Event(
			moduleId: 'booking',
			type: $type,
			parameters: $parameters,
		))->send();
	}

	private function processBookingCanceledEvent(JournalEvent $journalEvent): void
	{
		if (empty($journalEvent->data['booking']))
		{
			return;
		}

		$booking = Entity\Booking\Booking::mapFromArray($journalEvent->data['booking']);

		(new Chat())->onBookingCanceled($booking);
	}

	private function getHitsNeededAt(Entity\Booking\Booking $booking): array
	{
		$dateFrom = $booking->getDatePeriod()?->getDateFrom();
		if (!$dateFrom)
		{
			return [];
		}

		$result = [];

		// 5 minutes after booking has been started
		$result[] = $dateFrom->getTimestamp() + Time::SECONDS_IN_MINUTE * 5;

		// 24 hours before booking starts
		$result[] = $dateFrom->getTimestamp() - Time::SECONDS_IN_DAY;

		// at 8 am on booking date
		$result[] = $dateFrom->setTime(Time::DAYTIME_START_HOUR, 0)->getTimestamp();

		// at 20 pm on day before booking date (for reminders on early bookings)
		$result[] = $dateFrom->modify('-1 day')
			->setTime(Time::DAYTIME_END_HOUR - 1, 0)
			->getTimestamp()
		;

		return $result;
	}
}
