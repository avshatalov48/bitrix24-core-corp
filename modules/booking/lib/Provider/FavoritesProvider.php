<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\Favorites\Favorites;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\FavoritesRepositoryInterface;

class FavoritesProvider
{
	private FavoritesRepositoryInterface $repository;

	public function __construct()
	{
		$this->repository = Container::getFavoritesRepository();
	}

	public function getList(
		int $managerId,
		DatePeriod $datePeriod = null,
		bool $withCounters = false,
	): Favorites
	{
		$favorites = $this->repository->getList($managerId);

		if ($withCounters)
		{
			(new ResourceProvider())
				->withCounters(
					collection: $favorites->getResources(),
					managerId: $managerId,
					datePeriod: $datePeriod,
				);
		}

		return $favorites;
	}
}
