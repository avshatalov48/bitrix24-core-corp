<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service;

use Bitrix\Booking\Entity\Client\ClientCollection;
use Bitrix\Booking\Entity\EntityWithClientRelationInterface;
use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Integration\Crm\ClientAccessProvider;
use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Booking\Internals\Repository\BookingClientRepositoryInterface;

class ClientService
{
	public function __construct(
		private readonly BookingClientRepositoryInterface $bookingClientRepository
	)
	{
	}

	public function handleClientRelations(
		ClientCollection $clientCollection,
		EntityInterface & EntityWithClientRelationInterface $entity,
		EntityType $entityType
	): void
	{
		if ($clientCollection->isEmpty())
		{
			return;
		}

		$this->bookingClientRepository->link(
			$entity->getId(),
			$entityType,
			$clientCollection,
		);
		$entity->setClientCollection($clientCollection);

		$this->loadClientData($entity->getClientCollection());
	}

	public function handleClientRelationsUpdate(
		ClientCollection $newClients,
		ClientCollection $existingClients,
		ExternalDataCollection $newExternalData,
		EntityInterface & EntityWithClientRelationInterface $entity,
		EntityType $entityType,
	): bool
	{
		if ($newClients->isEmpty() && $existingClients->isEmpty())
		{
			Container::getProviderManager()::getCurrentProvider()
				?->getDataProvider()
				?->setClientsData(
					$newClients,
					$newExternalData
				);
		}

		if ($newClients->isEqual($existingClients))
		{
			return false;
		}

		/**
		 * If client's collections has changed we need to unlink every relation
		 * in order to recalculate IS_PRIMARY field
		 */
		$this->bookingClientRepository->unLink(
			$entity->getId(),
			$entityType,
			$existingClients
		);
		$this->bookingClientRepository->link($entity->getId(), $entityType, $newClients);

		return true;
	}

	public function loadClientData(ClientCollection $clientCollection): void
	{
		Container::getProviderManager()::getCurrentProvider()
			?->getClientProvider()
			?->loadClientDataForCollection($clientCollection)
		;
	}

	public function applyClientPermissions(ClientCollection $clientCollection): void
	{
		$readAccessMap = (new ClientAccessProvider())->getReadAccessMap($clientCollection);

		foreach ($clientCollection as $client)
		{
			$type = $client->getType()->getCode();
			$canRead = $readAccessMap[$type . '_' . $client->getId()]['read'];
			$client->setData([
				'data' => $canRead ? $client->getData() : null,
				'permissions' => [
					'read' => $canRead,
				],
			]);
		}
	}
}
