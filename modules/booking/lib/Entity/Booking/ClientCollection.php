<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Booking;

use Bitrix\Booking\Entity\BaseEntityCollection;
use Bitrix\Booking\Internals\Container;

/**
 * @method \Bitrix\Booking\Entity\Booking\Client|null getFirstCollectionItem()
 * @method Client[] getIterator()
 */
class ClientCollection extends BaseEntityCollection
{
	public function __construct(Client ...$clients)
	{
		foreach ($clients as $client)
		{
			$this->collectionItems[] = $client;
		}
	}

	public function getPrimaryClient(): Client|null
	{
		$moduleId = $this->getFirstCollectionItem()?->getType()?->getModuleId();
		if (!$moduleId)
		{
			return null;
		}

		$clientProvider = Container::getProviderManager()::getProviderByModuleId($moduleId)?->getClientProvider();
		if (!$clientProvider)
		{
			return null;
		}

		return $clientProvider->pickPrimaryClient($this);
	}

	public static function mapFromArray(array $props): self
	{
		$clients = array_map(
			static function ($client)
			{
				return Client::mapFromArray($client);
			},
			$props
		);

		return new ClientCollection(...$clients);
	}

	public function diff(ClientCollection $collectionToCompare): ClientCollection
	{
		return new ClientCollection(...$this->baseDiff($collectionToCompare));
	}
}
