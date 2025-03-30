<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Command\Favorites\AddFavoriteCommand;
use Bitrix\Booking\Command\Favorites\RemoveFavoriteCommand;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Provider\FavoritesProvider;
use Bitrix\Main\Engine\CurrentUser;

class Favorites extends BaseController
{
	public function listAction(): Entity\Favorites\Favorites
	{
		return (new FavoritesProvider())->getList((int)CurrentUser::get()->getId());
	}

	public function addAction(array $resourcesIds): Entity\Favorites\Favorites|null
	{
		$command = new AddFavoriteCommand(
			managerId: (int)CurrentUser::get()->getId(),
			resourcesIds: $resourcesIds,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getFavorites();
	}

	public function deleteAction(array $resourcesIds): array|null
	{
		$command = new RemoveFavoriteCommand(
			managerId: (int)CurrentUser::get()->getId(),
			resourcesIds: $resourcesIds,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getData();
	}
}
