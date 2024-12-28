<?php

namespace Bitrix\Sign\Item\Hr\EntitySelector;

use Bitrix\Main\ArgumentException;
use Bitrix\Sign\Contract\ItemCollection;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Type\Hr\EntitySelector\EntityType;

final class EntityCollection implements ItemCollection, \IteratorAggregate, \Countable
{
	/** @var \ArrayIterator<Entity> */
	private \ArrayIterator $iterator;

	public static function fromMemberCollection(MemberCollection $collection): EntityCollection
	{
		$entities = new EntityCollection();
		foreach ($collection as $member)
		{
			if ($member->entityId === null)
			{
				throw new ArgumentException('Member entity id is required');
			}

			if ($member->entityType === null)
			{
				throw new ArgumentException('Member entity type is required');
			}

			$entities->add(new Entity($member->entityId, EntityType::fromMember($member)));
		}
		return $entities;
	}

	public function __construct(Entity ...$items)
	{
		$this->iterator = new \ArrayIterator($items);
	}

	public function add(Entity $item): EntityCollection
	{
		$this->iterator->append($item);

		return $this;
	}

	public function filterByType(EntityType $entityType): EntityCollection
	{
		$filtered = new EntityCollection();
		/** @var Entity $item */
		foreach ($this->iterator as $item)
		{
			if ($item->entityType === $entityType)
			{
				$filtered->add($item);
			}
		}

		return $filtered;
	}

	public function toArray(): array
	{
		return $this->iterator->getArrayCopy();
	}

	public function getIterator(): \ArrayIterator
	{
		return $this->iterator;
	}

	public function count(): int
	{
		return $this->getIterator()->count();
	}
}
