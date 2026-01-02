<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Trait;

use Bitrix\Booking\Entity\BaseEntityCollection;
use Bitrix\Booking\Internals\Container;

trait ClientTrait
{
	public function withClientsData(BaseEntityCollection $entityCollection): self
	{
		$clientCollections = [];

		foreach ($entityCollection as $entity)
		{
			$clientCollections[] = $entity->getClientCollection();
		}

		Container::getProviderManager()::getCurrentProvider()
			?->getClientProvider()
			?->loadClientDataForCollection(...$clientCollections);

		return $this;
	}
}
