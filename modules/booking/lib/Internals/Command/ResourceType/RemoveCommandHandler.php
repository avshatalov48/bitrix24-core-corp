<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\ResourceType;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Exception\ResourceType\RemoveResourceTypeException;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalType;
use Bitrix\Booking\Internals\Query\Resource\ResourceFilter;

class RemoveCommandHandler
{
	public function __invoke(RemoveCommand $command): void
	{
		Container::getTransactionHandler()->handle(
			fn: $this->getRemoveTypeFunction($command),
			errType: RemoveResourceTypeException::class,
		);
	}

	private function getRemoveTypeFunction(RemoveCommand $command): callable
	{
		return function() use ($command)
		{
			$hasResourcesOfType = Container::getResourceRepository()->getList(
				limit: 1,
				filter: new ResourceFilter([
					'TYPE_ID' => $command->id,
				])
			)->isEmpty();

			if (!$hasResourcesOfType)
			{
				throw new RemoveResourceTypeException('The type can not be deleted. There are resources of  type');
			}

			Container::getResourceTypeRepository()->remove($command->id);

			Container::getJournalService()->append(
				new JournalEvent(
					entityId: $command->id,
					type: JournalType::ResourceTypeDeleted,
					data: $command->toArray(),
				),
			);
		};
	}
}
