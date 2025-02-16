<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Favorites;

use Bitrix\Booking\Entity\Favorites\Favorites;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Query\Resource\WithCounterHandler;
use Bitrix\Booking\Internals\Query\Resource\WithCounterRequest;
use Bitrix\Booking\Internals\Repository\FavoritesRepositoryInterface;

class GetListHandler
{
	private FavoritesRepositoryInterface $favoritesRepository;

	public function __construct()
	{
		$this->favoritesRepository = Container::getFavoritesRepository();
	}

	public function __invoke(GetListRequest $request): Favorites
	{
		$favorites = $this->favoritesRepository->getList($request->managerId);

		if ($request->withCounters)
		{
			$countersRequest = new WithCounterRequest(
				$favorites->getResources(),
				$request->managerId,
				$request->datePeriod
			);
			$resourcesWithCounters = (new WithCounterHandler())($countersRequest);
			$favorites->setResources($resourcesWithCounters);
		}

		return $favorites;
	}
}
