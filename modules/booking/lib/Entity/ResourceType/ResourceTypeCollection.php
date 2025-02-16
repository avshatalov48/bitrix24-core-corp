<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\ResourceType;

use Bitrix\Booking\Entity\BaseEntityCollection;

/**
 * @method \Bitrix\Booking\Entity\ResourceType\ResourceType|null getFirstCollectionItem()
 * @method ResourceType[] getIterator()
 */
class ResourceTypeCollection extends BaseEntityCollection
{
	public function __construct(ResourceType ...$resourceTypes)
	{
		foreach ($resourceTypes as $resourceType)
		{
			$this->collectionItems[] = $resourceType;
		}
	}

	public function findByCode(string $code, string $moduleId): ResourceType|null
	{
		foreach ($this as $resourceType)
		{
			if ($resourceType->getCode() === $code && $resourceType->getModuleId() === $moduleId)
			{
				return $resourceType;
			}
		}

		return null;
	}
}
