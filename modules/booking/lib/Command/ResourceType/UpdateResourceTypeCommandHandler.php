<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\ResourceType;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\ResourceType\UpdateResourceTypeException;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalType;

class UpdateResourceTypeCommandHandler
{
	public function __invoke(UpdateResourceTypeCommand $command): Entity\ResourceType\ResourceType
	{
		return Container::getTransactionHandler()->handle(
			fn: $this->getUpdateTypeFunction($command),
			errType: UpdateResourceTypeException::class,
		);
	}

	private function getUpdateTypeFunction(UpdateResourceTypeCommand $command): callable
	{
		return function() use ($command)
		{
			$resourceTypeId = Container::getResourceTypeRepository()->save($command->resourceType);
			$resourceType = Container::getResourceTypeRepository()->getById($resourceTypeId);
			if (!$resourceType)
			{
				throw new UpdateResourceTypeException();
			}

			Container::getJournalService()->append(
				new JournalEvent(
					entityId: $command->resourceType->getId(),
					type: JournalType::ResourceTypeUpdated,
					data: $command->toArray(),
				),
			);

			return $resourceType;
		};
	}
}
