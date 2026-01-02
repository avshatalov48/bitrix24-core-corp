<?php

namespace Bitrix\Booking\Command\WaitListItem;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\WaitListItem\RemoveWaitListItemException;
use Bitrix\Booking\Internals\Repository\TransactionHandlerInterface;
use Bitrix\Booking\Internals\Repository\WaitListItemRepositoryInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalServiceInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Provider\WaitListItemProvider;

class RemoveWaitListItemCommandHandler
{
	private readonly WaitListItemRepositoryInterface $waitListItemRepository;
	private readonly JournalServiceInterface $journalService;
	private readonly TransactionHandlerInterface $transactionHandler;
	private readonly WaitListItemProvider $waitListItemProvider;

	public function __construct()
	{
		$this->waitListItemRepository = Container::getWaitListItemRepository();
		$this->waitListItemProvider = new WaitListItemProvider();
		$this->journalService = Container::getJournalService();
		$this->transactionHandler = Container::getTransactionHandler();
	}

	public function __invoke(RemoveWaitListItemCommand $command): void
	{
		$this->transactionHandler->handle(
			fn: function () use ($command) {
				$waitListItem = $this->waitListItemProvider->getById(
					id: $command->id,
					userId: $command->removedBy,
				);
				if (!$waitListItem)
				{
					throw new RemoveWaitListItemException('wait list item not found');
				}

				$this->waitListItemRepository->remove($command->id);
				$waitListItem->setDeleted(true);

				$this->journalService->append(
					new JournalEvent(
						entityId: $command->id,
						type: JournalType::WaitListItemDeleted,
						data: [
							...$command->toArray(),
							'waitListItem' => $waitListItem->toArray(),
						],
					),
				);
			},
			errType: RemoveWaitListItemException::class,
		);
	}
}
