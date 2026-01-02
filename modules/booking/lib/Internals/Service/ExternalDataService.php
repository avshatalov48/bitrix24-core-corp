<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service;

use Bitrix\Booking\Entity\Client\ClientCollection;
use Bitrix\Booking\Entity\EntityWithClientRelationInterface;
use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Entity\EntityWithExternalDataRelationInterface;
use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Booking\Internals\Repository\BookingClientRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ORM\BookingExternalDataRepository;
use Bitrix\Booking\Internals\Service\DataLoader\ExternalDataLoader;

class ExternalDataService
{
	public function __construct(
		private readonly BookingClientRepositoryInterface $bookingClientRepository,
		private readonly BookingExternalDataRepository $bookingExternalDataRepository,
		private readonly ClientService $clientService,
		private ExternalDataLoader|null $externalDataLoader = null,
	)
	{
		$this->externalDataLoader = $externalDataLoader ?? new ExternalDataLoader();
	}

	public function handleExternalDataRelations(
		ExternalDataCollection $externalDataCollection,
		ClientCollection $clientCollection,
		EntityInterface & EntityWithClientRelationInterface & EntityWithExternalDataRelationInterface $entity,
		EntityType $entityType
	): void
	{
		if ($externalDataCollection->isEmpty())
		{
			return;
		}

		$this->bookingExternalDataRepository->link(
			$entity->getId(),
			$entityType,
			$externalDataCollection
		);
		$entity->setExternalDataCollection($externalDataCollection);

		// load booking external data
		$this->loadExternalData($externalDataCollection);

		// handle primary client
		if ($clientCollection->isEmpty())
		{
			Container::getProviderManager()::getCurrentProvider()
				?->getDataProvider()
				?->setClientsData(
					$clientCollection,
					$externalDataCollection
				);

			$this->bookingClientRepository->link(
				$entity->getId(),
				$entityType,
				$clientCollection,
			);

			// load booking external clients info
			$this->clientService->loadClientData($clientCollection);
			$entity->setClientCollection($clientCollection);
		}
	}

	public function handleExternalDataRelationsUpdate(
		ExternalDataCollection $newItems,
		ExternalDataCollection $existingItems,
		EntityInterface & EntityWithExternalDataRelationInterface $entity,
		EntityType $entityType,
	): void
	{
		if ($newItems->isEqual($existingItems))
		{
			return;
		}

		if (!$existingItems->isEmpty())
		{
			$unlink = $existingItems->diff($newItems);
			$this->bookingExternalDataRepository->unLink(
				$entity->getId(),
				$entityType,
				$unlink,
			);
		}

		if (!$newItems->isEmpty())
		{
			$link = $newItems->diff($existingItems);
			$this->bookingExternalDataRepository->link(
				$entity->getId(),
				$entityType,
				$link,
			);
		}
	}

	public function loadExternalData(ExternalDataCollection ...$externalDataCollection): void
	{
		$this->externalDataLoader->loadForCollections(...$externalDataCollection);
	}
}
