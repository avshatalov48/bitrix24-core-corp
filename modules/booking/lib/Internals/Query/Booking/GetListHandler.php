<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Booking;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Internals\Container;

class GetListHandler
{
	public function __invoke(GetListRequest $request): BookingCollection
	{
		$bookingCollection = Container::getBookingRepository()->getList(
			limit: $request->limit,
			offset: $request->offset,
			filter: $request->filter,
			sort: $request->sort,
			select: $request->select,
		);

		if ($request->withCounters)
		{
			(new WithCounterHandler())(
				new WithCounterRequest(
					bookingCollection: $bookingCollection,
					userId: $request->userId
				),
			);
		}

		if ($request->withClientData)
		{
			(new WithClientDataHandler())(
				new WithClientDataRequest(
					bookingCollection: $bookingCollection,
				),
			);
		}

		if ($request->withExternalData)
		{
			(new WithExternalDataHandler())(
				new WithExternalDataRequest(
					bookingCollection: $bookingCollection,
				),
			);
		}

		return $bookingCollection;
	}
}
