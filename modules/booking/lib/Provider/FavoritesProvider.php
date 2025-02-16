<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\Favorites\Favorites;
use Bitrix\Booking\Internals\Query\Favorites\GetListHandler;
use Bitrix\Booking\Internals\Query\Favorites\GetListRequest;

class FavoritesProvider
{
	public function getList(
		int $managerId,
		int $limit = null,
		int $offset = null,
		DatePeriod $datePeriod = null,
		bool $withCounters = false,
	): Favorites
	{
		$request = new GetListRequest(
			managerId: $managerId,
			datePeriod: $datePeriod,
			withCounters: $withCounters,
		);

		return (new GetListHandler())($request);
	}
}
