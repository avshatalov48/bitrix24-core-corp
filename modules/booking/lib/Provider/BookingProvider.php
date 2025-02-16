<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Internals\Feature\BookingConfirmLink;
use Bitrix\Booking\Internals\Query\Booking\GetBookingForManagerHandler;
use Bitrix\Booking\Internals\Query\Booking\GetByIdHandler;
use Bitrix\Booking\Internals\Query\Booking\GetIntersectionsHandler;
use Bitrix\Booking\Internals\Query\Booking\GetIntersectionsRequest;
use Bitrix\Booking\Internals\Query\Booking\GetListFilter;
use Bitrix\Booking\Internals\Query\Booking\GetListHandler;
use Bitrix\Booking\Internals\Query\Booking\GetListRequest;
use Bitrix\Booking\Internals\Query\Booking\GetListSelect;
use Bitrix\Booking\Internals\Query\Booking\GetListSort;

class BookingProvider
{
	public function getList(
		int $userId,
		int $limit = null,
		int $offset = null,
		array|null $filter = null,
		array|null $sort = null,
		array|null $select = null,
		bool $withCounters = false,
		bool $withClientData = false,
		bool $withExternalData = false,
	): BookingCollection
	{
		return (new GetListHandler())(
			new GetListRequest(
				userId: $userId,
				limit: $limit,
				offset: $offset,
				filter: new GetListFilter($filter ?? []),
				sort: new GetListSort($sort ?? []),
				select: new GetListSelect($select ?? []),
				withCounters: $withCounters,
				withClientData: $withClientData,
				withExternalData: $withExternalData,
			)
		);
	}

	public function getIntersectionsList(
		int $userId,
		Booking $booking,
	): BookingCollection
	{
		return (new GetIntersectionsHandler())(
			new GetIntersectionsRequest(
				booking: $booking,
			)
		);
	}

	public function getById(int $userId, int $id): Booking|null
	{
		return (new GetByIdHandler())($id);
	}

	public function getBookingForManager(int $id): Booking|null
	{
		return (new GetBookingForManagerHandler())($id);
	}

	public function getByHash(string $hash): Booking
	{
		return (new BookingConfirmLink())->getBookingByHash($hash);
	}
}
