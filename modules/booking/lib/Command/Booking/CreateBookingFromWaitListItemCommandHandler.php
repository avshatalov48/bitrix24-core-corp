<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Booking;

use Bitrix\Booking\Command\Booking\Trait\BookingChangesTrait;
use Bitrix\Booking\Command\WaitListItem\RemoveWaitListItemCommand;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\Booking\CreateBookingFromWaitListItemException;
use Bitrix\Booking\Internals\Exception\Booking\UpdateBookingException;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Repository\TransactionHandlerInterface;
use Bitrix\Booking\Internals\Repository\WaitListItemRepositoryInterface;
use Bitrix\Booking\Internals\Service\BookingService;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalServiceInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Service\Overbooking\OverbookingService;
use Bitrix\Booking\Internals\Service\ResourceService;
use Bitrix\Booking\Provider\WaitListItemProvider;

class CreateBookingFromWaitListItemCommandHandler
{
	use BookingChangesTrait;

	private TransactionHandlerInterface $transactionHandler;
	private WaitListItemProvider $waitListItemProvider;
	private WaitListItemRepositoryInterface $waitListItemRepository;
	private JournalServiceInterface $journalService;
	private ResourceService $resourceService;
	private BookingService $bookingService;
	private OverbookingService $overbookingService;
	private BookingRepositoryInterface $bookingRepository;

	public function __construct()
	{
		$this->transactionHandler = Container::getTransactionHandler();
		$this->waitListItemProvider = new WaitListItemProvider();
		$this->waitListItemRepository = Container::getWaitListItemRepository();
		$this->journalService = Container::getJournalService();
		$this->resourceService = Container::getResourceService();
		$this->bookingService = Container::getBookingService();
		$this->overbookingService = Container::getOverbookingService();
		$this->bookingRepository = Container::getBookingRepository();
	}

	public function __invoke(CreateBookingFromWaitListItemCommand $command): Entity\Booking\Booking
	{
		return $this->transactionHandler->handle(
			fn: function () use ($command) {
				$waitListItem = $this->waitListItemProvider->getById(
					$command->waitListItemId,
					$command->createdBy
				);

				if (!$waitListItem)
				{
					throw new CreateBookingFromWaitListItemException('wait list item not found');
				}

				$newBooking = $this->bookingService->buildFromWaitListItem(
					waitListItem: $waitListItem,
					resources: $command->resources,
					datePeriod: $command->datePeriod,
					createdBy: $command->createdBy,
					name: $command->name,
				);

				$commandResources = $newBooking->getResourceCollection();
				$loadedResources = $this->resourceService->loadResourceCollection($commandResources)->getNotDeleted();

				$notFoundIds = array_diff($commandResources->getEntityIds(), $loadedResources->getEntityIds());
				if (!empty($notFoundIds))
				{
					throw new UpdateBookingException(
						'Some resources were not found: ' . implode(', ', $notFoundIds)
					);
				}

				$newBooking->setResourceCollection($loadedResources);

				$this->bookingService->checkBookingBeforeCreating($newBooking);
				$intersectionResult = $this->bookingService->checkIntersection($newBooking, $command->allowOverbooking);

				$bookingEntity = $this->bookingService->create($newBooking, $command->createdBy);

				$addBookingCommand = new AddBookingCommand($command->createdBy, $bookingEntity);
				$this->journalService->append(
					new JournalEvent(
						entityId: $bookingEntity->getId(),
						type: JournalType::BookingAdded,
						data: array_merge(
							$addBookingCommand->toArray(),
							[
								'booking' => $bookingEntity->toArray(),
								'currentUserId' => $command->createdBy,
								'isOverbooking' => $intersectionResult->hasIntersections(),
							],
						),
					),
				);

				if ($intersectionResult->hasIntersections())
				{
					$events = $this->prepareOverbookingUpdateEvents(
						intersectionBookings: $intersectionResult->getBookingCollection(),
						updatedBy: $command->createdBy,
						isOverbooking: true,
					);
					array_map(fn(JournalEvent $event) => $this->journalService->append($event), $events);
				}

				$this->waitListItemRepository->remove($command->waitListItemId);
				$waitListItem->setDeleted(true);

				$removeWaitListItemCommand = new RemoveWaitListItemCommand(
					id: $command->waitListItemId,
					removedBy: $command->createdBy,
				);
				$this->journalService->append(
					new JournalEvent(
						entityId: $command->waitListItemId,
						type: JournalType::WaitListItemDeleted,
						data: [
							...$removeWaitListItemCommand->toArray(),
							'waitListItem' => $waitListItem->toArray(),
						],
					),
				);

				return $bookingEntity;
			},
			errType: CreateBookingFromWaitListItemException::class,
		);
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
