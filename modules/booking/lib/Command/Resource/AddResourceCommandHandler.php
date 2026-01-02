<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Resource;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\Resource\CreateResourceException;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceSlotRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceTypeRepositoryInterface;
use Bitrix\Booking\Internals\Repository\TransactionHandlerInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalServiceInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Service\ResourceService;

class AddResourceCommandHandler
{
	private TransactionHandlerInterface $transactionHandler;
	private ResourceRepositoryInterface $resourceRepository;
	private ResourceService $resourceService;
	private JournalServiceInterface $journalService;
	private ResourceTypeRepositoryInterface $resourceTypeRepository;
	private ResourceSlotRepositoryInterface $resourceSlotRepository;

	public function __construct()
	{
		$this->transactionHandler = Container::getTransactionHandler();
		$this->resourceRepository = Container::getResourceRepository();
		$this->resourceService = Container::getResourceService();
		$this->journalService = Container::getJournalService();
		$this->resourceTypeRepository = Container::getResourceTypeRepository();
		$this->resourceSlotRepository = Container::getResourceSlotRepository();
	}

	public function __invoke(AddResourceCommand $command): Entity\Resource\Resource
	{
		if (!$this->isValidType($command))
		{
			throw new CreateResourceException('ResourceType not found');
		}

		return $this->transactionHandler->handle(
			fn: function() use ($command) {
				// save resource
				$command->resource->setCreatedBy($command->createdBy);
				$resourceId = $this->resourceRepository->save($command->resource);
				$resource = $this->resourceRepository->getById($resourceId);

				if (!$resource)
				{
					throw new CreateResourceException();
				}

				$this->resourceService->handleResourceEntities($resource, $command->resource->getEntityCollection());

				// save slot ranges if any provided
				$this->handleSlotRanges($command, $resource);

				// fire new ResourceCreated event
				$this->journalService->append(
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

		return $this->resourceTypeRepository->isExists($typeId);
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

			$this->resourceSlotRepository->save($slotRanges);
			$resource->setSlotRanges($slotRanges);
		}
	}
}
