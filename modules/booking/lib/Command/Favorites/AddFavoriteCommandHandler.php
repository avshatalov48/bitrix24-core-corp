<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Favorites;

use Bitrix\Booking\Entity\Favorites\Favorites;
use Bitrix\Booking\Internals\Exception\Favorites\CreateFavoritesException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\FavoritesRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class AddFavoriteCommandHandler
{
	private ResourceRepositoryInterface $resourceRepository;
	private FavoritesRepositoryInterface $favoritesRepository;

	public function __construct()
	{
		$this->resourceRepository = Container::getResourceRepository();
		$this->favoritesRepository = Container::getFavoritesRepository();
	}

	public function __invoke(AddFavoriteCommand $command): Favorites
	{
		$primaryResourceIds = $this->resourceRepository
			->getList(
				filter: (new ConditionTree())
					->whereIn('ID', $command->resourcesIds)
					->where('IS_MAIN', '=', true),
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
