<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Resource;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Exception\Resource\CreateResourceException;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalType;

class AddCommandHandler
{
	public function __invoke(AddCommand $command): Entity\Resource\Resource
	{
		if (!$this->isValidType($command))
		{
			throw new CreateResourceException('ResourceType not found');
		}

		return Container::getTransactionHandler()->handle(
			fn: function() use ($command) {
				// save resource
				$command->resource->setCreatedBy($command->createdBy);
				$resource = Container::getResourceRepository()->save($command->resource);

				// save slot ranges if any provided
				$this->handleSlotRanges($command, $resource);

				// append ResourceCreated event to the journal
				Container::getJournalService()->append(
					new JournalEvent(
						entityId: $resource->getId(),
						type: JournalType::ResourceAdded,
						data: array_merge(
							$command->toArray(),
							[
								'resource' => $resource->toArray(),
								'currentUserId' => $command->createdBy,
							],
						),
					),
				);

				return $resource;
			},
			errType: CreateResourceException::class,
		);
	}

	private function isValidType(AddCommand $command): bool
	{
		$typeId = $command->resource->getType()?->getId();

		if (!$typeId)
		{
			return false;
		}

		$resourceType = Container::getResourceTypeRepository()->getById($typeId);

		if (!$resourceType)
		{
			return false;
		}

		return true;
	}

	private function handleSlotRanges(AddCommand $command, Entity\Resource\Resource $resource): void
	{
		if (!$command->resource->getSlotRanges()->isEmpty())
		{
			$slotRanges = $command->resource->getSlotRanges();

			/** @var Entity\Slot\Range $range */
			foreach ($slotRanges as $range)
			{
				$range->setResourceId($resource->getId());
				$range->setTypeId($resource->getType()->getId());
			}

			Container::getResourceSlotRepository()->save($slotRanges);
			$resource->setSlotRanges($slotRanges);
		}
	}
}
