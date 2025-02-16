<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Booking;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Internals\Container;

class GetIntersectionsHandler
{
	public function __invoke(GetIntersectionsRequest $request): BookingCollection
	{
		return Container::getBookingRepository()->getIntersectionsList($request->booking);
	}
}
