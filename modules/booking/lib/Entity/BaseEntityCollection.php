<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity;

use Bitrix\Main\Type\Contract\Arrayable;

class BaseEntityCollection implements \IteratorAggregate, Arrayable, \Countable
{
	/** @var $collectionItems EntityInterface[] */
	protected array $collectionItems = [];

	public function getCollectionItems(): array
	{
		return $this->collectionItems;
	}

	public function getFirstCollectionItem(): EntityInterface|null
	{
		return empty($this->collectionItems) ? null : $this->collectionItems[0] ?? null;
	}

	public function toArray(): array
	{
		return array_map(static fn ($collectionItem): array => $collectionItem->toArray(), $this->collectionItems);
	}

	/** @return EntityInterface[] */
	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->collectionItems);
	}

	public function count(): int
	{
		return count($this->collectionItems);
	}

	public function isEmpty(): bool
	{
		return empty($this->collectionItems);
	}

	public function add(EntityInterface $entity): void
	{
		$this->collectionItems[] = $entity;
	}

	public function isEqual(BaseEntityCollection $collectionToCompare): bool
	{
		if ($this->count() !== $collectionToCompare->count())
		{
			return false;
		}

		return empty($this->baseDiff($collectionToCompare));
	}

	public function getEntityIds(): array
	{
		$result = [];

		foreach ($this as $entity)
		{
			if ($entity->getId())
			{
				$result[] = $entity->getId();
			}
		}

		return array_unique($result);
	}

	public function getByEntityId(int|string $id): EntityInterface|null
	{
		foreach ($this->collectionItems as $entity)
		{
			if ($entity->getId() === $id)
			{
				return $entity;
			}
		}

		return null;
	}

	protected function baseDiff(BaseEntityCollection $collectionToCompare): array
	{
		return array_udiff(
			$this->getCollectionItems(),
			$collectionToCompare->getCollectionItems(),
			static fn (EntityInterface $entity1, EntityInterface $entity2) => $entity1 <=> $entity2,
		);
	}
}
