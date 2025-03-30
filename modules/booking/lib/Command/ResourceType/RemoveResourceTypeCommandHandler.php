<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\ResourceType;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\ResourceType\RemoveResourceTypeException;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class RemoveResourceTypeCommandHandler
{
	public function __invoke(RemoveResourceTypeCommand $command): void
	{
		$hasResourcesOfType = Container::getResourceRepository()->getList(
			limit: 1,
			filter: (new ConditionTree())
				->where('TYPE_ID', '=', $command->id),
		)->isEmpty();

		if (!$hasResourcesOfType)
		{
			throw new RemoveResourceTypeException('The type can not be deleted. There are resources of  type');
		}

		Container::getTransactionHandler()->handle(
			fn: $this->getRemoveTypeFunction($command),
			errType: RemoveResourceTypeException::class,
		);
	}

	private function getRemoveTypeFunction(RemoveResourceTypeCommand $command): callable
	{
		return function() use ($command)
		{
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
