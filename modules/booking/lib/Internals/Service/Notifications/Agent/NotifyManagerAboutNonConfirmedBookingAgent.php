<?php

namespace Bitrix\Booking\Internals\Service\Notifications\Agent;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalType;

class NotifyManagerAboutNonConfirmedBookingAgent
{
	public static function notify(int $bookingId): void
	{

		$booking = Container::getBookingRepository()->getById($bookingId);
		if (!$booking)
		{
			return;
		}

		if (
			$booking->isConfirmed()
			|| $booking->isVisitStatusKnown()
		)
		{
			return;
		}

		//@todo add im notification

		Container::getJournalService()
			->append(
				new JournalEvent(
					entityId: $booking->getId(),
					type: JournalType::BookingManagerConfirmNotificationSent,
					data: [],
				),
			);
	}
}
