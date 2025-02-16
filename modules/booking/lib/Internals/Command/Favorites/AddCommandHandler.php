<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Favorites;

use Bitrix\Booking\Entity\Favorites\Favorites;
use Bitrix\Booking\Exception\Favorites\CreateFavoritesException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Query\Resource\ResourceFilter;
use Bitrix\Booking\Internals\Repository\FavoritesRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;

class AddCommandHandler
{
	private ResourceRepositoryInterface $resourceRepository;
	private FavoritesRepositoryInterface $favoritesRepository;

	public function __construct()
	{
		$this->resourceRepository = Container::getResourceRepository();
		$this->favoritesRepository = Container::getFavoritesRepository();
	}

	public function __invoke(AddCommand $command): Favorites
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
			fn: function() use ($primaryResourceIds, $secondaryResourceIds, $command) {
				$this->favoritesRepository->addPrimary($command->managerId, $primaryResourceIds);
				$this->favoritesRepository->addSecondary($command->managerId, $secondaryResourceIds);
			},
			errType: CreateFavoritesException::class,
		);

		return $this->favoritesRepository->getList($command->managerId);
	}
}
