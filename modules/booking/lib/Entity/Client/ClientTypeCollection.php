<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Client;

use Bitrix\Booking\Entity\BaseEntityCollection;

/**
 * @method \Bitrix\Booking\Entity\Client\ClientType|null getFirstCollectionItem()
 * @method ClientType[] getIterator()
 */
class ClientTypeCollection extends BaseEntityCollection
{
	public function __construct(ClientType ...$clientTypes)
	{
		foreach ($clientTypes as $clientType)
		{
			$this->collectionItems[] = $clientType;
		}
	}
}
