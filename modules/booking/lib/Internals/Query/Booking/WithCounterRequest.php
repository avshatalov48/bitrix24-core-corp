<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Booking;

use Bitrix\Booking\Entity\Booking\BookingCollection;

class WithCounterRequest
{
	public function __construct(
		public readonly BookingCollection $bookingCollection,
		public readonly int $userId,
	)
	{
	}
}
