<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;
use Bitrix\Booking\Entity\ExternalData\ExternalDataItem;
use Bitrix\Booking\Entity\ExternalData\ItemType\CalendarEventItemType;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Integration\Calendar\CalendarEvent;
use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Booking\Internals\Model\Enum\ResourceLinkedEntityType;
use Bitrix\Booking\Internals\Model\ResourceLinkedEntityData\CalendarData;
use Bitrix\Booking\Internals\Repository\ORM\BookingExternalDataRepository;

class EventForBookingService
{
	private CalendarEvent $calendarEventService;

	public function __construct(
		private readonly BookingExternalDataRepository $externalDataRepository,
		CalendarEvent|null $calendarEventService = null,
	)
	{
		$this->calendarEventService = $calendarEventService ?? new CalendarEvent();
	}

	public function onBookingCreated(Booking $booking): void
	{
		if ($this->getLinkedEvent($booking))
		{
			return;
		}

		$calendarIntegrationConfig = $this->getCombinedCalendarIntegrationConfig($booking->getResourceCollection());
		if (!$calendarIntegrationConfig)
		{
			return;
		}

		$primaryClient = $booking->getClientCollection()->getPrimaryClient();
		$primaryResource = $booking->getResourceCollection()->getPrimary();

		$eventId = $this->calendarEventService->create(
			creatorId: $booking->getCreatedBy(),
			userIds: $calendarIntegrationConfig->getUserIds(),
			datePeriod: $booking->getDatePeriod(),
			resource: $primaryResource,
			client: $primaryClient,
			locationId: $calendarIntegrationConfig->getLocationId(),
			reminders: $calendarIntegrationConfig->getReminders(),
		);
		if (!$eventId)
		{
			return;
		}

		$this->linkToBooking($eventId, $booking);
	}

	public function onBookingUpdated(Booking $prevBooking, Booking $currentBooking): void
	{
		$linkedEvent = $this->getLinkedEvent($prevBooking);
		if (!$linkedEvent)
		{
			$this->onBookingCreated($currentBooking);

			return;
		}

		$currentCalendarIntegrationConfig = $this->getCombinedCalendarIntegrationConfig(
			$currentBooking->getResourceCollection()
		);
		if (!$currentCalendarIntegrationConfig)
		{
			$this->calendarEventService->delete((int)$linkedEvent->getValue());
			$this->unlinkFromBooking($currentBooking, $linkedEvent);

			return;
		}

		if ($this->isLinkedUsersChanged($prevBooking, $currentBooking))
		{
			$this->onBookingDeleted($prevBooking);
			$this->onBookingCreated($currentBooking);

			return;
		}

		if (!$this->shouldUpdateEvent($prevBooking, $currentBooking))
		{
			return;
		}

		$this->calendarEventService->update(
			eventId: (int)$linkedEvent->getValue(),
			newDatePeriod: $currentBooking->getDatePeriod(),
			resource: $currentBooking->getResourceCollection()->getPrimary(),
			client: $currentBooking->getClientCollection()->getPrimaryClient(),
			locationId: $currentCalendarIntegrationConfig->getLocationId(),
			reminders: $currentCalendarIntegrationConfig->getReminders(),
		);
	}

	public function onBookingDeleted(Booking $booking): void
	{
		$linkedEvent = $this->getLinkedEvent($booking);
		if (!$linkedEvent)
		{
			return;
		}

		$this->calendarEventService->delete((int)$linkedEvent->getValue());
		$this->unlinkFromBooking($booking, $linkedEvent);
	}

	public function onResourceIntegrationUpdated(Booking $booking): void
	{
		$linkedEvent = $this->getLinkedEvent($booking);
		if (!$linkedEvent)
		{
			$this->onBookingCreated($booking);

			return;
		}

		$calendarIntegrationConfig = $this->getCombinedCalendarIntegrationConfig(
			$booking->getResourceCollection()
		);

		if (!$calendarIntegrationConfig)
		{
			$this->onBookingDeleted($booking);

			return;
		}

		$shouldChangeCalendarEventHost = $this->shouldChangeHost($linkedEvent, $calendarIntegrationConfig);

		if ($shouldChangeCalendarEventHost)
		{
			$this->onBookingDeleted($booking);
			$this->onBookingCreated($booking);

			return;
		}

		$this->calendarEventService->update(
			eventId: (int)$linkedEvent->getValue(),
			newDatePeriod: $booking->getDatePeriod(),
			resource: $booking->getResourceCollection()->getPrimary(),
			client: $booking->getClientCollection()->getPrimaryClient(),
			userIds: $calendarIntegrationConfig->getUserIds(),
			locationId: $calendarIntegrationConfig->getLocationId(),
			reminders: $calendarIntegrationConfig->getReminders(),
		);
	}

	private function getLinkedEvent(Booking $booking): ExternalDataItem|null
	{
		return $booking->getExternalDataCollection()
			->filterByType((new CalendarEventItemType())->buildFilter())
			->getFirstCollectionItem()
		;
	}

	private function getCombinedCalendarIntegrationConfig(ResourceCollection $resourceCollection): CalendarData|null
	{
		$combinedConfig = new CalendarData();

		/** @var CalendarData $primaryResourceConfig */
		$primaryResourceConfig = $resourceCollection->getPrimary()?->getEntityCollection()
			->getByTypeAndId(ResourceLinkedEntityType::Calendar)
			->getFirstCollectionItem()
			?->getData()
		;

		if ($primaryResourceConfig)
		{
			$combinedConfig
				->setLocationId($primaryResourceConfig->getLocationId())
				->setReminders($primaryResourceConfig->getReminders())
				->setCheckAvailability($primaryResourceConfig->getCheckAvailability())
			;
		}

		foreach ($resourceCollection as $resource)
		{
			$calendarIntegrationConfig = $resource->getEntityCollection()
				->getByTypeAndId(ResourceLinkedEntityType::Calendar)
				->getFirstCollectionItem()
				?->getData()
			;
			if (!$calendarIntegrationConfig)
			{
				continue;
			}

			$combinedConfig->setUserIds([
				...$combinedConfig->getUserIds(),
				...$calendarIntegrationConfig->getUserIds(),
			]);
		}

		return $combinedConfig->getUserIds() ? $combinedConfig : null;
	}

	private function linkToBooking(int $eventId, Booking $booking): void
	{
		$linkedEvent = (new CalendarEventItemType())->createItem()->setValue((string)$eventId);

		$booking->getExternalDataCollection()->add($linkedEvent);
		$this->externalDataRepository->link(
			entityId: $booking->getId(),
			entityType: EntityType::Booking,
			collection: new ExternalDataCollection($linkedEvent),
		);
	}

	private function unlinkFromBooking(Booking $booking, ExternalDataItem $linkedEvent): void
	{
		$this->externalDataRepository->unLink(
			entityId: $booking->getId(),
			entityType: EntityType::Booking,
			collection: new ExternalDataCollection($linkedEvent),
		);

		$booking->setExternalDataCollection(
			$booking->getExternalDataCollection()
				->filterByType(
					(new CalendarEventItemType())->buildFilter(),
					true
				)
		);
	}

	private function shouldUpdateEvent(Booking $prevBooking, Booking $currentBooking): bool
	{
		$prevDatePeriod = $prevBooking->getDatePeriod();
		$currentDatePeriod = $currentBooking->getDatePeriod();

		$isDifferentDate = $prevDatePeriod->getDateFrom()->getTimestamp() !== $currentDatePeriod->getDateFrom()->getTimestamp()
			|| $prevDatePeriod->getDateTo()->getTimestamp() !== $currentDatePeriod->getDateTo()->getTimestamp()
		;

		if ($isDifferentDate)
		{
			return true;
		}

		if ($this->isClientsChanged($prevBooking, $currentBooking))
		{
			return true;
		}

		return false;
	}

	private function isClientsChanged(Booking $prevBooking, Booking $currentBooking): bool
	{
		if ($prevBooking->getClientCollection()->count() !== $currentBooking->getClientCollection()->count())
		{
			return true;
		}

		return !$prevBooking->getClientCollection()
			->diff($currentBooking->getClientCollection())
			->isEmpty()
		;
	}

	private function isLinkedUsersChanged(Booking $prevBooking, Booking $currentBooking): bool
	{
		$prevCalendarConfig = $this->getCombinedCalendarIntegrationConfig($prevBooking->getResourceCollection());
		$newCalendarConfig = $this->getCombinedCalendarIntegrationConfig($currentBooking->getResourceCollection());

		return !empty(
			array_diff(
				$prevCalendarConfig?->getUserIds() ?? [],
				$newCalendarConfig?->getUserIds() ?? []
			)
		);
	}

	private function shouldChangeHost(
		ExternalDataItem $linkedEvent,
		CalendarData $calendarData,
	): bool
	{
		$currentHost = $this->calendarEventService->getEventHostId((int)$linkedEvent->getValue());

		return !in_array($currentHost, $calendarData->getUserIds(), true);
	}
}
