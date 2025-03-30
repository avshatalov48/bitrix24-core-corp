<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\ResourceType;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\ResourceType\CreateResourceTypeException;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalType;

class AddResourceTypeCommandHandler
{
	public function __invoke(AddResourceTypeCommand $command): Entity\ResourceType\ResourceType
	{
		return Container::getTransactionHandler()->handle(
			fn: $this->getNewTypeFunction($command),
			errType: CreateResourceTypeException::class,
		);
	}

	private function getNewTypeFunction(AddResourceTypeCommand $command): callable
	{
		return function() use ($command)
		{
			$resourceTypeId = Container::getResourceTypeRepository()->save($command->resourceType);
			$resourceType = Container::getResourceTypeRepository()->getById($resourceTypeId);

			if (!$resourceType)
			{
				throw new CreateResourceTypeException();
			}

			Container::getJournalService()->append(
				new JournalEvent(
					entityId: $resourceType->getId(),
					type: JournalType::ResourceTypeAdded,
					data: array_merge(
						$command->toArray(),
						[
							'resourceType' => $resourceType->toArray(),
							'currentUserId' => $command->createdBy,
						],
					),
				),
			);

			return $resourceType;
		};
	}
}
