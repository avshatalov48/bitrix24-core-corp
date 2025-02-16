<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Booking;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Exception\Booking\CreateBookingException;
use Bitrix\Booking\Exception\Exception;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalServiceInterface;
use Bitrix\Booking\Internals\Journal\JournalType;
use Bitrix\Booking\Internals\Query\Resource\ResourceFilter;
use Bitrix\Booking\Internals\Repository\BookingClientRepositoryInterface;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ORM\BookingExternalDataRepository;
use Bitrix\Booking\Internals\Repository\ORM\BookingResourceRepository;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceTypeRepositoryInterface;
use Bitrix\Booking\Internals\Repository\TransactionHandlerInterface;

class AddCommandHandler
{
	private ResourceRepositoryInterface $resourceRepository;
	private ResourceTypeRepositoryInterface $resourceTypeRepository;
	private BookingRepositoryInterface $bookingRepository;
	private BookingClientRepositoryInterface $bookingClientRepository;
	private BookingExternalDataRepository $bookingExternalDataRepository;
	private BookingResourceRepository $bookingResourceRepository;
	private TransactionHandlerInterface $transactionHandler;
	private JournalServiceInterface $journalService;

	public function __construct()
	{
		$this->bookingRepository = Container::getBookingRepository();
		$this->bookingClientRepository = Container::getBookingClientRepository();
		$this->bookingExternalDataRepository = Container::getBookingExternalDataRepository();
		$this->resourceRepository = Container::getResourceRepository();
		$this->resourceTypeRepository = Container::getResourceTypeRepository();
		$this->bookingResourceRepository = Container::getBookingResourceRepository();
		$this->transactionHandler = Container::getTransactionHandler();
		$this->journalService = Container::getJournalService();
	}

	public function __invoke(AddCommand $command): Entity\Booking\Booking
	{
		$this->loadResourceCollection($command->booking);

		if ($command->booking->getResourceCollection()->isEmpty())
		{
			throw new CreateBookingException('Empty resource collection');
		}

		if ($command->booking->getDatePeriod() === null)
		{
			throw new CreateBookingException('Date period is not specified');
		}

		$intersectingBookings = $this->bookingRepository->getIntersectionsList($command->booking);

		if (!$intersectingBookings->isEmpty())
		{
			throw new CreateBookingException(
				'Some resources are unavailable for the requested time range: '
				. implode(',', $intersectingBookings->getEntityIds()),
				Exception::CODE_BOOKING_INTERSECTION
			);
		}

		if ($command->booking->isAutoConfirmed())
		{
			$command->booking->setConfirmed(true);
		}

		return $this->transactionHandler->handle(
			fn: function() use ($command) {
				$command->booking->setCreatedBy($command->createdBy);
				$result = $this->bookingRepository->save($command->booking);

				$booking = $this->bookingRepository->getById($result->getId(), $command->createdBy);

				$resourceCollection = $command->booking->getResourceCollection();
				$this->bookingResourceRepository->link($booking, $resourceCollection);
				$booking->setResourceCollection($resourceCollection);

				$clientCollection = $command->booking->getClientCollection();
				if (!$clientCollection->isEmpty())
				{
					$this->bookingClientRepository->link($booking, $clientCollection);
					$booking->setClientCollection($clientCollection);

					Container::getProviderManager()::getCurrentProvider()
						?->getClientProvider()
						->loadClientDataForCollection($booking->getClientCollection());
				}

				$externalDataCollection = $command->booking->getExternalDataCollection();
				if (!$externalDataCollection->isEmpty())
				{
					$this->bookingExternalDataRepository->link($booking, $externalDataCollection);
					$booking->setExternalDataCollection($externalDataCollection);
				}

				$this->journalService->append(
					new JournalEvent(
						entityId: $booking->getId(),
						type: JournalType::BookingAdded,
						data: array_merge(
							$command->toArray(),
							[
								'booking' => $booking->toArray(),
								'currentUserId' => $command->createdBy,
							],
						),
					),
				);

				return $booking;
			},
			errType: CreateBookingException::class,
		);
	}

	private function loadResourceCollection(Entity\Booking\Booking $booking): void
	{
		$resourceIds = $this->handleExternalResources($booking) ?? [];
		/** @var Resource $resource */
		foreach ($booking->getResourceCollection() as $resource)
		{
			$resourceIds[] = $resource->getId();
		}

		$result = new ResourceCollection();
		/**
		 * Resource order matters here!
		 * Primary resource always goes first
		 */
		foreach ($resourceIds as $resourceId)
		{
			$resource = $this->resourceRepository->getById($resourceId);
			if ($resource)
			{
				$result->add($resource);
			}
		}

		$booking->setResourceCollection($result);
	}

	//@todo we have duplicates method in Bitrix\Booking\Internals\Command\Booking\UpdateCommandHandler
	private function handleExternalResources(Entity\Booking\Booking $booking): array
	{
		$externalResourceIds = [];

		/** @var Entity\Resource\Resource $resource */
		foreach ($booking->getResourceCollection() as $resource)
		{
			if (!$resource->isExternal())
			{
				continue;
			}

			$externalResourceId = $this->transactionHandler->handle(
				fn: function() use ($resource) {
					if (!$resource?->getType()?->getModuleId())
					{
						throw new CreateBookingException('ModuleId of resource type is not specified');
					}

					if (!$resource?->getType()?->getCode())
					{
						throw new CreateBookingException('Code of resource type is not specified');
					}

					$externalType = $this->resourceTypeRepository->getByModuleIdAndCode(
						$resource->getType()->getModuleId(),
						$resource->getType()->getCode(),
					);

					if ($externalType === null)
					{
						$externalType = $this->resourceTypeRepository->save($resource->getType());
					}

					$externalResource = $this->resourceRepository->getList(
						filter: new ResourceFilter([
							'TYPE_ID' => $externalType->getId(),
							'EXTERNAL_ID' => $resource->getExternalId(),
						]),
					)->getFirstCollectionItem();

					if ($externalResource === null)
					{
						$resource->setType($externalType);
						$externalResource = $this->resourceRepository->save($resource);
					}

					return $externalResource->getId();
				},
				errType: CreateBookingException::class,
			);
			$externalResourceIds[] = $externalResourceId;
		}

		return $externalResourceIds;
	}
}
