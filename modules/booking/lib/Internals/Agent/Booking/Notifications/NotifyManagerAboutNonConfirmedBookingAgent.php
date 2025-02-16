<?php

namespace Bitrix\Booking\Internals\Agent\Booking\Notifications;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalType;

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
