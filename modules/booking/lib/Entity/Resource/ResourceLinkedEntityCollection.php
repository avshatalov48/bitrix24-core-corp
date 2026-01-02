<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Resource;

use Bitrix\Booking\Entity\BaseEntityCollection;
use Bitrix\Booking\Internals\Model\Enum\ResourceLinkedEntityType;

/**
 * @method ResourceLinkedEntity[] getIterator()
 * @method ResourceLinkedEntity getFirstCollectionItem()
 */
class ResourceLinkedEntityCollection extends BaseEntityCollection
{
	public function __construct(ResourceLinkedEntity ...$entities)
	{
		foreach ($entities as $entity)
		{
			$this->collectionItems[] = $entity;
		}
	}

	public static function mapFromArray(array $props): self
	{
		$entityCollection = new self();
		foreach ($props as $entityCollectionProps)
		{
			$linkedEntity = ResourceLinkedEntity::mapFromArray($entityCollectionProps);
			$entityCollection->add($linkedEntity);
		}

		return $entityCollection;
	}

	public function diff(ResourceLinkedEntityCollection $collectionToCompare): ResourceLinkedEntityCollection
	{
		/** @var ResourceLinkedEntity[] $compareItems */
		$compareItems = $collectionToCompare->getCollectionItems();
		$filtered = [];

		/** @var ResourceLinkedEntity $entity */
		foreach ($this->getCollectionItems() as $entity)
		{
			$found = false;
			foreach ($compareItems as $compareEntity)
			{
				if (
					$entity->getEntityId() === $compareEntity->getEntityId()
					&& $entity->getEntityType() === $compareEntity->getEntityType()
				)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
			{
				$filtered[] = $entity;
			}
		}

		return new self(...$filtered);
	}

	public function getByTypeAndId(
		ResourceLinkedEntityType $entityType,
		int|null $entityId = null,
	): ResourceLinkedEntityCollection
	{
		$collection = new self();

		/** @var ResourceLinkedEntity $resourceLinkedEntity */
		foreach ($this as $resourceLinkedEntity)
		{
			if ($resourceLinkedEntity->getEntityType() !== $entityType)
			{
				continue;
			}

			if ($entityId && $resourceLinkedEntity->getEntityId() !== $entityId)
			{
				continue;
			}

			$collection->add($resourceLinkedEntity);
		}

		return $collection;
	}
}
