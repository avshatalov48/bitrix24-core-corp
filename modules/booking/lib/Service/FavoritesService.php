<?php

declare(strict_types=1);

namespace Bitrix\Booking\Service;

use Bitrix\Booking\Entity\Favorites\Favorites;
use Bitrix\Booking\Internals\Command\Favorites\AddCommand;
use Bitrix\Booking\Internals\Command\Favorites\AddCommandHandler;
use Bitrix\Booking\Internals\Command\Favorites\RemoveCommand;
use Bitrix\Booking\Internals\Command\Favorites\RemoveCommandHandler;

class FavoritesService
{
	public function add(int $managerId, array $resourcesIds): Favorites
	{
		$command = new AddCommand(managerId: $managerId, resourcesIds: $resourcesIds);

		return (new AddCommandHandler())($command);
	}

	public function delete(int $managerId, array $resourcesIds): void
	{
		$command = new RemoveCommand(managerId: $managerId, resourcesIds: $resourcesIds);

		(new RemoveCommandHandler())($command);
	}
}
