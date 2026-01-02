<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Overbooking;

use Bitrix\Booking\Entity\Booking\BookingCollection;

class IntersectionStatusUpdateResult
{
	public function __construct(
		public readonly BookingCollection $intersecting,
		public readonly BookingCollection $nonIntersecting,
	) {
	}
}
