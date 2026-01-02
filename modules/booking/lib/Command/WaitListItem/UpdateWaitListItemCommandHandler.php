<?php

namespace Bitrix\Booking\Command\WaitListItem;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\WaitListItem\UpdateWaitListItemException;
use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Booking\Internals\Repository\TransactionHandlerInterface;
use Bitrix\Booking\Internals\Repository\WaitListItemRepositoryInterface;
use Bitrix\Booking\Internals\Service\ClientService;
use Bitrix\Booking\Internals\Service\ExternalDataService;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalServiceInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Provider\WaitListItemProvider;

class UpdateWaitListItemCommandHandler
{
	private readonly TransactionHandlerInterface $transactionHandler;
	private readonly WaitListItemProvider $waitListItemProvider;
	private readonly WaitListItemRepositoryInterface $waitListItemRepository;
	private readonly ClientService $clientService;
	private readonly ExternalDataService $externalDataService;
	private readonly JournalServiceInterface $journalService;

	public function __construct()
	{
		$this->transactionHandler = Container::getTransactionHandler();
		$this->waitListItemProvider = new WaitListItemProvider();
		$this->waitListItemRepository = Container::getWaitListItemRepository();
		$this->clientService = Container::getClientService();
		$this->externalDataService = Container::getExternalDataService();
		$this->journalService = Container::getJournalService();
	}

	public function __invoke(UpdateWaitListItemCommand $command): Entity\WaitListItem\WaitListItem
	{
		$currentWaitListItem = $this->waitListItemProvider->getById(
			$command->waitListItem->getId(),
			$command->updatedBy,
			false,
		);

		if (!$currentWaitListItem)
		{
			throw new UpdateWaitListItemException('Wait list item not found');
		}

		return $this->transactionHandler->handle(
			fn: function () use ($command, $currentWaitListItem) {
				$isUpdated = $this->clientService->handleClientRelationsUpdate(
					newClients: $command->waitListItem->getClientCollection(),
					existingClients: $currentWaitListItem->getClientCollection(),
					newExternalData: $command->waitListItem->getExternalDataCollection(),
					entity: $command->waitListItem,
					entityType: EntityType::WaitList,
				);
				if ($isUpdated)
				{
					$this->journalService->append(
						new JournalEvent(
							entityId: $command->waitListItem->getId(),
							type: JournalType::WaitListItemClientUpdated,
							data: [],
						),
					);
				}

				$this->externalDataService->handleExternalDataRelationsUpdate(
					newItems: $command->waitListItem->getExternalDataCollection(),
					existingItems: $currentWaitListItem->getExternalDataCollection(),
					entity: $command->waitListItem,
					entityType: EntityType::WaitList,
				);

				$waitListItemId = $this->waitListItemRepository->save(
					$command->waitListItem,
					UpdateWaitListItemException::class,
				);
				$waitListItem = $this->waitListItemProvider->getById($waitListItemId, $command->updatedBy);

				if (!$waitListItem)
				{
					throw new UpdateWaitListItemException();
				}

				$this->journalService->append(
					new JournalEvent(
						entityId: $waitListItemId,
						type: JournalType::WaitListItemUpdated,
						data: [
							...$command->toArray(),
							'waitListItem' => $waitListItem->toArray(),
							'currentUserId' => $command->updatedBy,
						],
					),
				);

				return $waitListItem;
			},
			errType: UpdateWaitListItemException::class,
		);
	}
}
