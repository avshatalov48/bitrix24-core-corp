<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Exception\WaitListItem\CreateWaitListItemException;
use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Booking\Internals\Repository\WaitListItemRepositoryInterface;

class WaitListItemService
{
	public function __construct(
		private readonly WaitListItemRepositoryInterface $waitListItemRepository,
		private readonly ClientService $clientService,
		private readonly ExternalDataService $externalDataService,
	)
	{
	}

	public function create(
		Entity\WaitListItem\WaitListItem $waitListItem,
		int $userId,
	): Entity\WaitListItem\WaitListItem
	{
		$waitListItem->setCreatedBy($userId);
		$waitListItemId = $this->waitListItemRepository->save($waitListItem);
		$waitListItemEntity = $this->waitListItemRepository->getById($waitListItemId);

		if (!$waitListItemEntity)
		{
			throw new CreateWaitListItemException();
		}

		$this->clientService->handleClientRelations(
			$waitListItem->getClientCollection(),
			$waitListItemEntity,
			EntityType::WaitList,
		);
		$this->externalDataService->handleExternalDataRelations(
			$waitListItem->getExternalDataCollection(),
			$waitListItem->getClientCollection(),
			$waitListItemEntity,
			EntityType::WaitList,
		);

		return $waitListItemEntity;
	}

	public function createWaitListItemFromBooking(
		Entity\Booking\Booking $booking,
		int $userId,
	): Entity\WaitListItem\WaitListItem
	{
		$waitListItem = new Entity\WaitListItem\WaitListItem();
		$waitListItem
			->setNote($booking->getNote())
			->setCreatedBy($userId)
			->setClientCollection(
				new Entity\Client\ClientCollection(...$booking->getClientCollection()->getCollectionItems())
			)
			->setExternalDataCollection(
				new Entity\ExternalData\ExternalDataCollection(
					...$booking->getExternalDataCollection()->getCollectionItems()
				)
			)
		;

		return $waitListItem;
	}
}
