<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Resource;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\Resource\UpdateResourceException;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalServiceInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Repository\FavoritesRepositoryInterface;
use Bitrix\Booking\Internals\Service\ResourceService;

class UpdateResourceCommandHandler
{
	private FavoritesRepositoryInterface $favoritesRepository;
	private ResourceService $resourceService;
	private JournalServiceInterface $journalService;

	public function __construct()
	{
		$this->favoritesRepository = Container::getFavoritesRepository();
		$this->resourceService = Container::getResourceService();
		$this->journalService = Container::getJournalService();
	}

	public function __invoke(UpdateResourceCommand $command): Entity\Resource\Resource
	{
		$currentResource = Container::getResourceRepository()->getById($command->resource->getId());

		if (!$currentResource || $currentResource->isDeleted())
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
				$resourceEntityChanges = $this->resourceService->handleResourceEntities(
					$currentResource,
					$command->resource->getEntityCollection(),
				);

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
				$this->journalService->append(
					new JournalEvent(
						entityId: $command->resource->getId(),
						type: JournalType::ResourceUpdated,
						data: array_merge(
							$command->toArray(),
							[
								'resource' => $updatedResource->toArray(),
								'currentUserId' => $command->updatedBy,
								'resourceEntityChanges' => $resourceEntityChanges,
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
