<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Provider\FavoritesProvider;
use Bitrix\Booking\Service\FavoritesService;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Engine\CurrentUser;

class Favorites extends BaseController
{
	public function listAction(): Entity\Favorites\Favorites
	{
		return (new FavoritesProvider())->getList((int)CurrentUser::get()->getId());
	}

	public function addAction(array $resourcesIds): Entity\Favorites\Favorites
	{
		return $this->handleRequest(function() use ($resourcesIds)
		{
			return (new FavoritesService())->add(
				managerId: (int)CurrentUser::get()->getId(),
				resourcesIds: $resourcesIds,
			);
		});
	}

	public function deleteAction(array $resourcesIds): array
	{
		return $this->handleRequest(function() use ($resourcesIds)
		{
			(new FavoritesService())->delete(
				managerId: (int)CurrentUser::get()->getId(),
				resourcesIds: $resourcesIds,
			);

			return [];
		});
	}
}
