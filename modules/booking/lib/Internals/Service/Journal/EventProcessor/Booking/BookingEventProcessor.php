<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Journal\EventProcessor\Booking;

use Bitrix\Booking\Command\Booking\AddBookingCommand;
use Bitrix\Booking\Command\Booking\UpdateBookingCommand;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Integration\Im\Chat;
use Bitrix\Booking\Internals\Service\Agent\ComingSoonBookingAgentManager;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\EventProcessor;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalEventCollection;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Service\Time;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Entity;
use Bitrix\Main\Event;
use DateTimeImmutable;
use DateInterval;

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
				JournalType::BookingComingSoonNotificationSent => $this->processBookingComingSoonNotificationSentEvent($event),
				JournalType::BookingConfirmed => $this->processBookingConfirmedEvent($event),
				JournalType::BookingDelayedCounterActivated => $this->processBookingDelayedCounterActivatedEvent($event),
				JournalType::BookingConfirmCounterActivated => $this->processBookingConfirmCounterActivatedEvent($event),
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

		$this->sendBitrixEvent(type: 'onBookingAdd', parameters: [
			'booking' => $booking,
			'isOverbooking' => $journalEvent->data['isOverbooking'],
		]);

		$hitsAt = $this->getCommonHitsAt($booking);
		$this->addInfoHitsAt($booking, $hitsAt);

		if (!empty($hitsAt))
		{
			$this->sendBitrixEvent(type: 'onHitsNeeded', parameters: [
				'hitsAt' => $hitsAt,
			]);
		}

		(new ComingSoonBookingAgentManager())->schedule($booking);
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
			'isOverbooking' => $journalEvent->data['isOverbooking'],
		]);

		$hitsAt = $this->getCommonHitsAt($updatedBooking);
		if (!empty($hitsAt))
		{
			$this->sendBitrixEvent(type: 'onHitsNeeded', parameters: [
				'hitsAt' => $hitsAt,
			]);
		}

		if ($prevBooking && $updatedBooking)
		{
			(new ComingSoonBookingAgentManager())->updateSchedule($prevBooking, $updatedBooking);
		}
	}

	private function processBookingDeletedEvent(JournalEvent $journalEvent): void
	{
		$this->sendBitrixEvent(type: 'onBookingDelete', parameters: [
			'bookingId' => $journalEvent->entityId,
		]);

		$bookingCollection = Container::getBookingRepository()->getList(
			limit: 1,
			filter: new BookingFilter([
				'ID' => $journalEvent->entityId,
				'INCLUDE_DELETED' => true,
			]),
			select: (new BookingSelect([
				'EXTERNAL_DATA',
			]))->prepareSelect(),
		);

		if ($booking = $bookingCollection->getFirstCollectionItem())
		{
			(new ComingSoonBookingAgentManager())->unSchedule($booking);
			Container::getEventForBookingService()->onBookingDeleted($booking);
		}
	}

	private function processBookingCanceledEvent(JournalEvent $journalEvent): void
	{
		if (empty($journalEvent->data['booking']))
		{
			return;
		}

		$booking = Entity\Booking\Booking::mapFromArray($journalEvent->data['booking']);

		(new Chat())->onBookingCanceled($booking);

		$this->sendBitrixEvent(type: 'onBookingStatusUpdated', parameters: [
			'booking' => $booking,
			'status' => BookingStatusEnum::CanceledByClient->value,
		]);
	}

	private function processBookingConfirmedEvent(JournalEvent $journalEvent): void
	{
		//TODO: review indirect detection of confirm by user
		$hash = $journalEvent->data['hash'] ?? null;
		$status = $hash ? BookingStatusEnum::ConfirmedByClient : BookingStatusEnum::ConfirmedByManager;

		$booking = Entity\Booking\Booking::mapFromArray($journalEvent->data['booking']);

		$this->sendBitrixEvent(type: 'onBookingStatusUpdated', parameters: [
			'booking' => $booking,
			'status' => $status->value,
		]);
	}

	private function processBookingComingSoonNotificationSentEvent(JournalEvent $journalEvent): void
	{
		$booking = Entity\Booking\Booking::mapFromArray($journalEvent->data['booking']);

		$this->sendBitrixEvent(type: 'onBookingStatusUpdated', parameters: [
			'booking' => $booking,
			'status' => BookingStatusEnum::ComingSoon->value,
		]);
	}

	private function processBookingDelayedCounterActivatedEvent(JournalEvent $journalEvent): void
	{
		$booking = Entity\Booking\Booking::mapFromArray($journalEvent->data['booking']);

		$this->sendBitrixEvent(type: 'onBookingStatusUpdated', parameters: [
			'booking' => $booking,
			'status' => BookingStatusEnum::DelayedCounterActivated->value,
		]);
	}

	private function processBookingConfirmCounterActivatedEvent(JournalEvent $journalEvent): void
	{
		$booking = Entity\Booking\Booking::mapFromArray($journalEvent->data['booking']);

		$this->sendBitrixEvent(type: 'onBookingStatusUpdated', parameters: [
			'booking' => $booking,
			'status' => BookingStatusEnum::ConfirmCounterActivated->value,
		]);
	}

	private function getCommonHitsAt(Entity\Booking\Booking $booking): array
	{
		$dateFrom = $booking->getDatePeriod()?->getDateFrom();
		$primaryResource = $booking->getPrimaryResource();

		if (!$dateFrom || !$primaryResource)
		{
			return [];
		}

		$result = [];

		$this->addConfirmationHitsAt($dateFrom, $primaryResource, $result);
		$this->addReminderHitsAt($dateFrom, $primaryResource, $result);
		$this->addStartSoonHitsAt($dateFrom, $result);
		$this->addDelayHitsAt($dateFrom, $primaryResource, $result);

		return $result;
	}

	private function addInfoHitsAt(Entity\Booking\Booking $booking, array &$result): void
	{
		$createdAt = $booking->getCreatedAt();
		$primaryResource = $booking->getPrimaryResource();

		if ($createdAt === null || !$primaryResource)
		{
			return;
		}

		if ($primaryResource->getInfoNotificationDelay() === 0)
		{
			/**
			 * Allow manager up to 5 minutes to fill client details like phone number
			 */
			for ($i = 1; $i <= 5; $i++)
			{
				$result[] = $createdAt + $i * Time::SECONDS_IN_MINUTE;
			}
		}
		else
		{
			$result[] = $createdAt + $primaryResource->getInfoNotificationDelay();
		}
	}

	private function addConfirmationHitsAt(
		DateTimeImmutable $dateFrom,
		Entity\Resource\Resource $primaryResource,
		array &$result
	): void
	{
		$result = [];

		$delay = $primaryResource->getConfirmationNotificationDelay();
		$confirmAt = $dateFrom->sub(new DateInterval('PT' . $delay . 'S'));
		$actualConfirmAt = $this->getActualHitDateConsideringDelay($confirmAt, $delay);

		$result[] = $actualConfirmAt->getTimestamp();

		$repetitions = $primaryResource->getConfirmationNotificationRepetitions();
		$repetitionsInterval = $primaryResource->getConfirmationNotificationRepetitionsInterval();
		for ($repetition = 1; $repetition <= $repetitions; $repetition++)
		{
			$nextConfirmAt = $actualConfirmAt->add(
				new DateInterval('PT' . $repetition * $repetitionsInterval . 'S')
			);

			$result[] = $this->getActualHitDateConsideringDelay($nextConfirmAt, $delay)->getTimestamp();
		}

		$result[] = $dateFrom->getTimestamp() - $primaryResource->getConfirmationCounterDelay();
	}

	private function addReminderHitsAt(
		DateTimeImmutable $dateFrom,
		Entity\Resource\Resource $primaryResource,
		array &$result
	): void
	{
		$reminderNotificationDelay = $primaryResource->getReminderNotificationDelay();
		if ($reminderNotificationDelay === Entity\Enum\Notification\ReminderNotificationDelay::Morning->value)
		{
			// at start hour on booking date
			$result[] = $dateFrom->setTime(Time::DAYTIME_START_HOUR, 0)->getTimestamp();

			// at the end of the day before booking
			$result[] = $dateFrom->modify('-1 day')
				->setTime(Time::DAYTIME_END_HOUR - 1, 0)
				->getTimestamp()
			;
		}
		else
		{
			$remindAt = $dateFrom->sub(new DateInterval('PT' . $reminderNotificationDelay . 'S'));
			$actualRemindAt = $this->getActualHitDateConsideringDelay($remindAt, $reminderNotificationDelay);

			$result[] = $actualRemindAt->getTimestamp();
		}
	}

	private function addStartSoonHitsAt(DateTimeImmutable $dateFrom, array &$result): void
	{
		$result[] = $dateFrom->getTimestamp() - Time::SECONDS_IN_MINUTE * 15;
	}

	private function addDelayHitsAt(
		DateTimeImmutable $dateFrom,
		Entity\Resource\Resource $primaryResource,
		array &$result
	): void
	{
		$result[] = $dateFrom->getTimestamp() + $primaryResource->getDelayedNotificationDelay();
		$result[] = $dateFrom->getTimestamp() + $primaryResource->getDelayedCounterDelay();
	}

	private function getActualHitDateConsideringDelay(DateTimeImmutable $date, int $delay): DateTimeImmutable
	{
		$needPrecise = $delay < Time::SECONDS_IN_DAY;

		if ($needPrecise || Time::isWorkingTime($date))
		{
			return $date;
		}

		if ((int)$date->format('H') < Time::DAYTIME_START_HOUR)
		{
			return $date
				->setTime(Time::DAYTIME_START_HOUR, 0);
		}
		else
		{
			return $date
				->modify('+1 day')
				->setTime(Time::DAYTIME_START_HOUR, 0);
		}
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
