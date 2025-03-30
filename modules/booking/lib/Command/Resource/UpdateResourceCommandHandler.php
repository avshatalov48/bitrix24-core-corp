<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Resource;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\Resource\UpdateResourceException;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Repository\FavoritesRepositoryInterface;

class UpdateResourceCommandHandler
{
	private FavoritesRepositoryInterface $favoritesRepository;

	public function __construct()
	{
		$this->favoritesRepository = Container::getFavoritesRepository();
	}

	public function __invoke(UpdateResourceCommand $command): Entity\Resource\Resource
	{
		$currentResource = Container::getResourceRepository()->getById($command->resource->getId());

		if (!$currentResource)
		{
			throw new UpdateResourceException('Resource not found');
		}

		if (!$this->isValidType($command))
		{
			throw new UpdateResourceException('ResourceType not found');
		}

		return Container::getTransactionHandler()->handle(
			fn: function() use ($command, $currentResource) {
				// update slot ranges
				$this->handleSlotRanges($command, $currentResource);

				// update resource
				$updatedResourceId = Container::getResourceRepository()->save($command->resource);
				$updatedResource = Container::getResourceRepository()->getById($updatedResourceId);
				if (!$updatedResource)
				{
					throw new UpdateResourceException();
				}

				// push resource to the favorites
				if (!$currentResource->isMain() && $updatedResource->isMain())
				{
					$this->favoritesRepository->pushPrimary([$updatedResource->getId()]);
				}

				// fire new ResourceUpdated event
				Container::getJournalService()->append(
					new JournalEvent(
						entityId: $command->resource->getId(),
						type: JournalType::ResourceUpdated,
						data: array_merge(
							$command->toArray(),
							[
								'resource' => $updatedResource->toArray(),
								'currentUserId' => $command->updatedBy,
							],
						),
					),
				);

				return $updatedResource;
			},
			errType: UpdateResourceException::class,
		);
	}

	private function isValidType(UpdateResourceCommand $command): bool
	{
		$resourceTypeId = $command->resource->getType()?->getId();

		if ($resourceTypeId)
		{
			return Container::getResourceTypeRepository()->isExists($resourceTypeId);
		}

		return true;
	}

	private function handleSlotRanges(UpdateResourceCommand $command, Entity\Resource\Resource $resource): void
	{
		$newRanges = $command->resource->getSlotRanges();
		$existedRanges = $resource->getSlotRanges();

		/** @var Entity\Slot\Range $range */
		foreach ($newRanges as $range)
		{
			$range->setResourceId($resource->getId());
			$range->setTypeId($resource->getType()->getId());
		}

		if ($newRanges->isEqual($existedRanges))
		{
			return;
		}

		if (!$existedRanges->isEmpty())
		{
			$rangesToRemove = $existedRanges->diff($newRanges);
			Container::getResourceSlotRepository()->remove($rangesToRemove);
		}

		if (!$newRanges->isEmpty())
		{
			$rangesToAdd = $newRanges->diff($existedRanges);
			Container::getResourceSlotRepository()->save($rangesToAdd);
		}
	}
}
