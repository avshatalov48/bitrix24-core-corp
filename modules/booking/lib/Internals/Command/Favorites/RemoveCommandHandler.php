<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Favorites;

use Bitrix\Booking\Exception\Favorites\RemoveFavoritesException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Query\Resource\ResourceFilter;
use Bitrix\Booking\Internals\Repository\FavoritesRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;

class RemoveCommandHandler
{
	private FavoritesRepositoryInterface $favoritesRepository;
	private ResourceRepositoryInterface $resourceRepository;

	public function __construct()
	{
		$this->favoritesRepository = Container::getFavoritesRepository();
		$this->resourceRepository = Container::getResourceRepository();
	}

	public function __invoke(RemoveCommand $command): void
	{
		$primaryResourceIds = $this->resourceRepository
			->getList(
				filter: new ResourceFilter([
					'ID' => $command->resourcesIds,
					'IS_MAIN' => true
				]),
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
