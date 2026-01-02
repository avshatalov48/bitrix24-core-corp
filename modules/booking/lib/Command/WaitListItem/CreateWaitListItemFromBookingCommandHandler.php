<?php

namespace Bitrix\Booking\Command\WaitListItem;

use Bitrix\Booking\Command\Booking\RemoveBookingCommand;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\WaitListItem\CreateWaitListItemFromBookingException;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Repository\TransactionHandlerInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalServiceInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Service\WaitListItemService;

class CreateWaitListItemFromBookingCommandHandler
{
	private TransactionHandlerInterface $transactionHandler;
	private BookingRepositoryInterface $bookingRepository;
	private JournalServiceInterface $journalService;
	private WaitListItemService $waitListItemService;

	public function __construct()
	{
		$this->transactionHandler = Container::getTransactionHandler();
		$this->bookingRepository = Container::getBookingRepository();
		$this->journalService = Container::getJournalService();
		$this->waitListItemService = Container::getWaitListItemService();
	}

	public function __invoke(CreateWaitListItemFromBookingCommand $command): Entity\WaitListItem\WaitListItem
	{
		return $this->transactionHandler->handle(
			fn: function() use ($command) {
				$booking = $this->bookingRepository->getById($command->bookingId, $command->createdBy);
				if (!$booking)
				{
					throw new CreateWaitListItemFromBookingException('booking not found');
				}

				$newWaitListItem = $this->waitListItemService->createWaitListItemFromBooking(
					$booking,
					$command->createdBy,
				);

				$waitListItem = $this->waitListItemService->create($newWaitListItem, $command->createdBy);

				$createWaitListCommand = new AddWaitListItemCommand($command->createdBy, $waitListItem);
				$this->journalService->append(
					new JournalEvent(
						entityId: $waitListItem->getId(),
						type: JournalType::WaitListItemAdded,
						data: array_merge(
							$createWaitListCommand->toArray(),
							[
								'currentUserId' => $command->createdBy,
							],
						),
					),
				);

				$this->bookingRepository->remove($command->bookingId);

				$removeBookingCommand = new RemoveBookingCommand($command->bookingId, $command->createdBy);
				$this->journalService->append(
					new JournalEvent(
						entityId: $command->bookingId,
						type: JournalType::BookingDeleted,
						data: $removeBookingCommand->toArray(),
					),
				);

				return $waitListItem;
			},
			errType: CreateWaitListItemFromBookingException::class,
		);
	}
}
