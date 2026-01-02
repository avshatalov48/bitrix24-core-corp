<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Booking;

use Bitrix\Booking\Command\Booking\Trait\BookingChangesTrait;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Exception\Booking\UpdateBookingException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Booking\Internals\Repository\ORM\BookingResourceRepository;
use Bitrix\Booking\Internals\Service\BookingService;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalServiceInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Repository\BookingClientRepositoryInterface;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ORM\BookingExternalDataRepository;
use Bitrix\Booking\Internals\Repository\TransactionHandlerInterface;
use Bitrix\Booking\Internals\Service\Overbooking\OverbookingService;
use Bitrix\Booking\Internals\Service\ResourceService;
use Bitrix\Booking\Provider\BookingProvider;

class UpdateBookingCommandHandler
{
	use BookingChangesTrait;

	private BookingRepositoryInterface $bookingRepository;
	private BookingClientRepositoryInterface $bookingClientRepository;
	private BookingExternalDataRepository $bookingExternalDataRepository;
	private BookingResourceRepository $bookingResourceRepository;
	private TransactionHandlerInterface $transactionHandler;
	private JournalServiceInterface $journalService;
	private BookingService $bookingService;
	private OverbookingService $overbookingService;
	private BookingProvider $bookingProvider;
	private ResourceService $resourceService;

	public function __construct()
	{
		$this->bookingRepository = Container::getBookingRepository();
		$this->bookingClientRepository = Container::getBookingClientRepository();
		$this->bookingExternalDataRepository = Container::getBookingExternalDataRepository();
		$this->bookingResourceRepository = Container::getBookingResourceRepository();
		$this->transactionHandler = Container::getTransactionHandler();
		$this->journalService = Container::getJournalService();
		$this->bookingService = Container::getBookingService();
		$this->overbookingService = Container::getOverbookingService();
		$this->resourceService = Container::getResourceService();
		$this->bookingProvider = new BookingProvider();
	}

	public function __invoke(UpdateBookingCommand $command): Entity\Booking\Booking
	{
		$currentBooking = $this->bookingRepository->getById($command->booking->getId());

		if (!$currentBooking)
		{
			throw new UpdateBookingException('Booking not found');
		}

		$commandResources = clone $command->booking->getResourceCollection();
		$loadedResources = $this->resourceService->loadResourceCollection($commandResources);

		$notFoundIds = array_diff($commandResources->getEntityIds(), $loadedResources->getEntityIds());
		if (!empty($notFoundIds))
		{
			throw new UpdateBookingException(
				'Some resources were not found: ' . implode(', ', $notFoundIds)
			);
		}

		$command->booking->setResourceCollection($loadedResources);

		try
		{
			$this->bookingService->checkBookingBeforeUpdating($currentBooking, $command->booking);

			$intersectionResult = $this->bookingService->checkIntersection(
				booking: $command->booking,
				allowOverbooking: $command->allowOverbooking,
			);
		}
		catch (\Throwable $exception)
		{
			throw new UpdateBookingException($exception->getMessage());
		}

		$booking = $this->transactionHandler->handle(
			fn: function() use ($command, $currentBooking, $intersectionResult) {
				$this->handleResources($command->booking, $currentBooking);
				$this->handleClients($command, $currentBooking);
				$this->handleExternalData($command, $currentBooking);

				$bookingId = $this->bookingRepository->save($command->booking);
				$booking = $this->bookingRepository->getById($bookingId);
				if (!$booking)
				{
					throw new UpdateBookingException();
				}

				// load booking external clients info
				Container::getProviderManager()::getCurrentProvider()
					?->getClientProvider()
					?->loadClientDataForCollection($booking->getClientCollection());

				// load booking external data
				$this->bookingProvider->withExternalData(new Entity\Booking\BookingCollection($booking));

				Container::getProviderManager()::getCurrentProvider()
					?->getDataProvider()
					?->updateBindings($booking, $currentBooking);

				$this->journalService->append(
					new JournalEvent(
						entityId: $command->booking->getId(),
						type: JournalType::BookingUpdated,
						data: [
							...$command->toArray(),
							'booking' => $booking->toArray(),
							'currentUserId' => $command->updatedBy,
							'prevBooking' => $currentBooking->toArray(),
							'isOverbooking' => $intersectionResult->hasIntersections(),
						],
					),
				);

				$this->processBookingChanges(
					$currentBooking,
					$booking,
					$intersectionResult,
					$command->updatedBy,
				);

				if (!$currentBooking->isConfirmed() && $booking->isConfirmed())
				{
					$this->journalService->append(
						new JournalEvent(
							entityId: $command->booking->getId(),
							type: JournalType::BookingConfirmed,
							data: [
								'booking' => $booking->toArray(),
							],
						)
					);
				}

				// TODO: if BookingRepository::getById refactored and stop return counters without condition
				// refactor this, check usages for proper counters loading
				if ($command->updatedBy > 0)
				{
					// update counters cause it may be changed during booking update process
					$this->bookingProvider->withCounters(
						new Entity\Booking\BookingCollection($booking),
						$command->updatedBy
					);
				}

				return $booking;
			},
			errType: UpdateBookingException::class,
		);

		try
		{
			Container::getEventForBookingService()->onBookingUpdated($currentBooking, $booking);
		}
		catch (\Throwable $e)
		{
			// TODO: add error handling
		}

		return $booking;
	}

	private function handleResources(Entity\Booking\Booking $newBooking, Entity\Booking\Booking $currentBooking): void
	{
		$newResources = $newBooking->getResourceCollection();
		$existingResources = $currentBooking->getResourceCollection();

		if ($newResources->isEqual($existingResources))
		{
			return;
		}

		if (!$existingResources->isEmpty())
		{
			$this->bookingResourceRepository->unLink($currentBooking, $existingResources);
		}

		if (!$newResources->isEmpty())
		{
			$this->bookingResourceRepository->link($currentBooking, $newResources);
		}

		$newBooking->setResourceCollection($newResources);
	}

	private function handleClients(UpdateBookingCommand $command, Entity\Booking\Booking $booking): void
	{
		$newClients = $command->booking->getClientCollection();
		$existingClients = $booking->getClientCollection();

		if ($newClients->isEmpty() && $existingClients->isEmpty())
		{
			Container::getProviderManager()::getCurrentProvider()
				?->getDataProvider()
				?->setClientsData(
					$newClients,
					$command->booking->getExternalDataCollection()
				);
		}

		if ($newClients->isEqual($existingClients))
		{
			return;
		}

		/**
		 * If client's collections has changed we need to unlink every relation
		 * in order to recalculate IS_PRIMARY field
		 */
		$this->bookingClientRepository->unLink($booking->getId(), EntityType::Booking, $existingClients);
		$this->bookingClientRepository->link($booking->getId(), EntityType::Booking, $newClients);

		$this->journalService->append(
			new JournalEvent(
				entityId: $command->booking->getId(),
				type: JournalType::BookingClientsUpdated,
				data: [],
			),
		);
	}

	private function handleExternalData(UpdateBookingCommand $command, Entity\Booking\Booking $booking): void
	{
		$newItems = $command->booking->getExternalDataCollection();
		$existingItems = $booking->getExternalDataCollection();

		if ($newItems->isEqual($existingItems))
		{
			return;
		}

		if (!$existingItems->isEmpty())
		{
			$unlink = $existingItems->diff($newItems);
			$this->bookingExternalDataRepository->unLink($booking->getId(), EntityType::Booking, $unlink);
		}

		if (!$newItems->isEmpty())
		{
			$link = $newItems->diff($existingItems);
			$this->bookingExternalDataRepository->link($booking->getId(), EntityType::Booking, $link);
		}
	}

	protected function getOverbookingService(): OverbookingService
	{
		return $this->overbookingService;
	}

	protected function getBookingRepository(): BookingRepositoryInterface
	{
		return $this->bookingRepository;
	}

	protected function getJournalService(): JournalServiceInterface
	{
		return $this->journalService;
	}
}
