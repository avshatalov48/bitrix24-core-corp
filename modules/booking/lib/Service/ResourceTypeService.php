<?php

declare(strict_types=1);

namespace Bitrix\Booking\Service;

use Bitrix\Booking\Internals\Command;
use Bitrix\Booking\Entity;

class ResourceTypeService
{
	public function create(
		int $userId,
		Entity\ResourceType\ResourceType $resourceType,
		Entity\Slot\RangeCollection|null $rangeCollection = null,
	): Entity\ResourceType\ResourceType
	{
		$command = new Command\ResourceType\AddCommand(
			createdBy: $userId,
			resourceType: $resourceType,
			rangeCollection: $rangeCollection,
		);

		return (new Command\ResourceType\AddCommandHandler())($command);
	}

	public function update(
		int $userId,
		Entity\ResourceType\ResourceType $resourceType,
		Entity\Slot\RangeCollection|null $rangeCollection = null,
	): Entity\ResourceType\ResourceType
	{
		$command = new Command\ResourceType\UpdateCommand(
			updatedBy: $userId,
			resourceType: $resourceType,
			rangeCollection: $rangeCollection,
		);

		return (new Command\ResourceType\UpdateCommandHandler())($command);
	}

	public function delete(int $userId, int $id): void
	{
		$command = new Command\ResourceType\RemoveCommand(
			id: $id,
			removedBy: $userId,
		);

		(new Command\ResourceType\RemoveCommandHandler())($command);
	}
}
