<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Booking;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Internals\Container;

class GetByIdHandler
{
	public function __invoke(int $bookingId): ?Booking
	{
		return Container::getBookingRepository()->getById($bookingId);
	}
}
