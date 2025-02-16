<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Booking;

use Bitrix\Booking\Entity\Booking\Booking;

class GetIntersectionsRequest
{
	public function __construct(
		public readonly Booking $booking
	)
	{
	}
}
