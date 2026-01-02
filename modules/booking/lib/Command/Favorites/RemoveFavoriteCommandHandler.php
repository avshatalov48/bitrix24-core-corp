<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Favorites;

use Bitrix\Booking\Internals\Exception\Favorites\RemoveFavoritesException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\FavoritesRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;
use Bitrix\Booking\Provider\Params\Resource\ResourceFilter;
use Bitrix\Booking\Provider\Params\Resource\ResourceSelect;

class RemoveFavoriteCommandHandler
{
	private FavoritesRepositoryInterface $favoritesRepository;
	private ResourceRepositoryInterface $resourceRepository;

	public function __construct()
	{
		$this->favoritesRepository = Container::getFavoritesRepository();
		$this->resourceRepository = Container::getResourceRepository();
	}

	public function __invoke(RemoveFavoriteCommand $command): void
	{
		$primaryResourceIds = $this->resourceRepository
			->getList(
				filter: (new ResourceFilter([
					'ID' => $command->resourcesIds,
					'IS_MAIN' => true,
				])),
				select: new ResourceSelect(),
			)
			->getEntityIds()
		;

		$secondaryResourceIds = $this->favoritesRepository->filterSecondary(
			resourceIds: $command->resourcesIds,
			primaryResourceIds: $primaryResourceIds,
		);

		Container::getTransactionHandler()->handle(
			fn: function() use ($command, $primaryResourceIds, $secondaryResourceIds) {
				$this->favoritesRepository->removePrimary($command->managerId, $primaryResourceIds);
				$this->favoritesRepository->removeSecondary($command->managerId, $secondaryResourceIds);
			},
			errType: RemoveFavoritesException::class,
		);
	}
}
