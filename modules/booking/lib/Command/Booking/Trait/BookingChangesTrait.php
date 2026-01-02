<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Booking\Trait;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalServiceInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Service\Overbooking\IntersectionResult;
use Bitrix\Booking\Internals\Service\Overbooking\OverbookingService;

trait BookingChangesTrait
{
	abstract protected function getOverbookingService(): OverbookingService;
	abstract protected function getBookingRepository(): BookingRepositoryInterface;
	abstract protected function getJournalService(): JournalServiceInterface;

	private function processBookingChanges(
		Booking $oldBookingState,
		Booking $newBookingState,
		IntersectionResult $intersectionResult,
		int $userId,
	): void
	{
		$bookingChanges = $this->getOverbookingService()->getIsOverbookingUpdates(
			oldBookingState: $oldBookingState,
			newBookingState: $newBookingState,
			newBookingStateIntersections: $intersectionResult,
		);
		if ($bookingChanges->notOverbooked || $bookingChanges->overbooked)
		{
			array_map(fn (JournalEvent $event) => $this->getJournalService()->append($event), [
				...$this->prepareOverbookingUpdateEvents(
					$bookingChanges->notOverbooked,
					$userId,
					false,
				),
				...$this->prepareOverbookingUpdateEvents(
					$bookingChanges->overbooked,
					$userId,
					true,
				),
			]);
		}
	}

	/**
	 * @return JournalEvent[]
	 */
	private function prepareOverbookingUpdateEvents(
		BookingCollection $intersectionBookings,
		int $updatedBy,
		bool $isOverbooking,
	): array
	{
		$events = [];
		foreach ($intersectionBookings as $intersectionBooking)
		{
			$bookingWithRelations = $this->getBookingRepository()->getById(
				id: $intersectionBooking->getId(),
				userId: $updatedBy,
			);
			if (!$bookingWithRelations)
			{
				continue;
			}

			$events[] = new JournalEvent(
				entityId: $bookingWithRelations->getId(),
				type: JournalType::BookingUpdated,
				data: [
					'booking' => $bookingWithRelations->toArray(),
					'updatedBy' => $updatedBy,
					'allowOverbooking' => true,
					'currentUserId' => $updatedBy,
					'prevBooking' => $bookingWithRelations->toArray(),
					'isOverbooking' => $isOverbooking,
				]
			);
		}

		return $events;
	}
}
