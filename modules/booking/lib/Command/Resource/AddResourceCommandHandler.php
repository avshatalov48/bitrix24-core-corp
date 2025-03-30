<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Resource;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\Resource\CreateResourceException;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalType;

class AddResourceCommandHandler
{
	public function __invoke(AddResourceCommand $command): Entity\Resource\Resource
	{
		if (!$this->isValidType($command))
		{
			throw new CreateResourceException('ResourceType not found');
		}

		return Container::getTransactionHandler()->handle(
			fn: function() use ($command) {
				// save resource
				$command->resource->setCreatedBy($command->createdBy);
				$resourceId = Container::getResourceRepository()->save($command->resource);
				$resource = Container::getResourceRepository()->getById($resourceId);

				if (!$resource)
				{
					throw new CreateResourceException();
				}

				// save slot ranges if any provided
				$this->handleSlotRanges($command, $resource);

				// fire new ResourceCreated event
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

	private function isValidType(AddResourceCommand $command): bool
	{
		$typeId = $command->resource->getType()?->getId();

		if (!$typeId)
		{
			return false;
		}

		return Container::getResourceTypeRepository()->isExists($typeId);
	}

	private function handleSlotRanges(AddResourceCommand $command, Entity\Resource\Resource $resource): void
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
