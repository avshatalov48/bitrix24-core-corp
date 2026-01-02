<?php

namespace Bitrix\Booking\Command\WaitListItem;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\WaitListItem\CreateWaitListItemException;
use Bitrix\Booking\Internals\Repository\TransactionHandlerInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalServiceInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Service\WaitListItemService;

class AddWaitListItemCommandHandler
{
	private TransactionHandlerInterface $transactionHandler;
	private WaitListItemService $waitListItemService;
	private JournalServiceInterface $journalService;

	public function __construct()
	{
		$this->transactionHandler = Container::getTransactionHandler();
		$this->waitListItemService = Container::getWaitListItemService();
		$this->journalService = Container::getJournalService();
	}

	public function __invoke(AddWaitListItemCommand $command): Entity\WaitListItem\WaitListItem
	{
		return $this->transactionHandler->handle(
			fn: function () use ($command) {
				$waitListItem = $this->waitListItemService->create($command->waitListItem, $command->createdBy);

				$this->journalService->append(
					new JournalEvent(
						entityId: $waitListItem->getId(),
						type: JournalType::WaitListItemAdded,
						data: array_merge(
							$command->toArray(),
							[
								'waitListItem' => $waitListItem->toArray(),
								'currentUserId' => $command->createdBy,
							],
						),
					),
				);

				return $waitListItem;
			},
			errType: CreateWaitListItemException::class,
		);
	}
}
