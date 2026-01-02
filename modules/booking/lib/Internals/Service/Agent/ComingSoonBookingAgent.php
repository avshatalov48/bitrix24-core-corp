<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Agent;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalType;

class ComingSoonBookingAgent
{
	public static function execute(int $bookingId): string|null
	{
		$booking = Container::getBookingRepository()->getById($bookingId);
		if (
			!$booking
			// if booking already started not need to trigger event
			|| $booking->getDatePeriod()?->getDateFrom()->getTimestamp() <= time()
		)
		{
			return null;
		}

		Container::getJournalService()
			->append(
				new JournalEvent(
					entityId: $booking->getId(),
					type: JournalType::BookingComingSoonNotificationSent,
					data: [
						'booking' => $booking->toArray(),
					],
				),
			);

		return null;
	}

	public static function getName(int $bookingId): string
	{
		return sprintf('\\' . static::class . '::execute(%d);', $bookingId);
	}
}
