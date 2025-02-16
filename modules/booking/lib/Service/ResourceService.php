<?php

declare(strict_types=1);

namespace Bitrix\Booking\Service;

use Bitrix\Booking\Internals\Command;
use Bitrix\Booking\Entity;

class ResourceService
{
	public function create(
		int $userId,
		Entity\Resource\Resource $resource,
		int|null $copies = null,
	): Entity\Resource\Resource
	{
		$command = new Command\Resource\AddCommand(
			createdBy: $userId,
			resource: $resource,
			copies: $copies,
		);

		return (new Command\Resource\AddCommandHandler())($command);
	}

	public function update(int $userId, Entity\Resource\Resource $resource): Entity\Resource\Resource
	{
		$command = new Command\Resource\UpdateCommand(
			updatedBy: $userId,
			resource: $resource,
		);

		return (new Command\Resource\UpdateCommandHandler())($command);
	}

	public function delete(int $userId, int $id): void
	{
		$command = new Command\Resource\RemoveCommand(
			id: $id,
			removedBy: $userId,
		);

		(new Command\Resource\RemoveCommandHandler())($command);
	}
}
