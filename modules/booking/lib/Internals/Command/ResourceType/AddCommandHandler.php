<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\ResourceType;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Exception\ResourceType\CreateResourceTypeException;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalType;

class AddCommandHandler
{
	public function __invoke(AddCommand $command): Entity\ResourceType\ResourceType
	{
		return Container::getTransactionHandler()->handle(
			fn: $this->getNewTypeFunction($command),
			errType: CreateResourceTypeException::class,
		);
	}

	private function getNewTypeFunction(AddCommand $command): callable
	{
		return function() use ($command)
		{
			$resourceType = Container::getResourceTypeRepository()->save($command->resourceType);

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
