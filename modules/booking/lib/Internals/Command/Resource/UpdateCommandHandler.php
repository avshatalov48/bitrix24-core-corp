<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Resource;

use Bitrix\Booking\Access\ResourceAction;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Exception\PermissionDenied;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Exception\Resource\UpdateResourceException;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalType;
use Bitrix\Booking\Internals\Repository\FavoritesRepositoryInterface;

class UpdateCommandHandler
{
	private FavoritesRepositoryInterface $favoritesRepository;

	public function __construct()
	{
		$this->favoritesRepository = Container::getFavoritesRepository();
	}

	public function __invoke(UpdateCommand $command): Entity\Resource\Resource
	{
		$currentResource = Container::getResourceRepository()->getById($command->resource->getId());

		if (!$currentResource)
		{
			throw new UpdateResourceException('Resource not found');
		}

		if (!Container::getResourceAccessController()::can(
			userId: $command->updatedBy,
			action: ResourceAction::Update,
			itemId: $command->resource->getId(),
		))
		{
			throw new PermissionDenied();
		}

		return Container::getTransactionHandler()->handle(
			fn: function() use ($command, $currentResource) {
				// update slot ranges
				$this->handleSlotRanges($command, $currentResource);

				// update resource
				$updatedResource = Container::getResourceRepository()->save($command->resource);

				// push resource to the favorites
				if (!$currentResource->isMain() && $updatedResource->isMain())
				{
					$this->favoritesRepository->pushPrimary([$updatedResource->getId()]);
				}

				// append ResourceUpdated event to the journal
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

	private function handleSlotRanges(UpdateCommand $command, Entity\Resource\Resource $resource): void
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
