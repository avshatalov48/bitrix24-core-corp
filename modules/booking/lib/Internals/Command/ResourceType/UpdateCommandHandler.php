<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\ResourceType;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Exception\ResourceType\UpdateResourceTypeException;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalType;

class UpdateCommandHandler
{
	public function __invoke(UpdateCommand $command): Entity\ResourceType\ResourceType
	{
		return Container::getTransactionHandler()->handle(
			fn: $this->getUpdateTypeFunction($command),
			errType: UpdateResourceTypeException::class,
		);
	}

	private function getUpdateTypeFunction(UpdateCommand $command): callable
	{
		return function() use ($command)
		{
			$resourceType = Container::getResourceTypeRepository()->save($command->resourceType);

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
