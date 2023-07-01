<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline;

use ArrayIterator;
use Bitrix\Crm\ItemIdentifier;

class Bindings implements \IteratorAggregate
{
	/** @var ItemIdentifier[]  */
	private array $identifiers;

	public function __construct(ItemIdentifier ...$identifier)
	{
		$this->identifiers = $identifier;
	}

	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->identifiers);
	}

	public function isEmpty(): bool
	{
		return empty($this->identifiers);
	}

	public function toArray(
		string $entityIdKey = 'ENTITY_ID',
		string $entityTypeIdKey = 'ENTITY_TYPE_ID',
		string $categoryId = 'CATEGORY_ID'
	): array
	{
		$identifiers = [];
		foreach ($this->identifiers as $identifier)
		{
			$identifiers[] = [
				$entityIdKey => $identifier->getEntityId(),
				$entityTypeIdKey => $identifier->getEntityTypeId(),
				$categoryId => $identifier->getCategoryId(),
			];
		}

		return $identifiers;
	}

	public function extract(): ?ItemIdentifier
	{
		return array_shift($this->identifiers);
	}

	public function add(ItemIdentifier $itemIdentifier): void
	{
		foreach ($this->identifiers as $identifier)
		{
			if ($this->equals($identifier, $itemIdentifier))
			{
				return;
			}
		}

		$this->identifiers[] = $itemIdentifier;
	}

	private function equals(ItemIdentifier $a, ItemIdentifier $b): bool
	{
		return ($a->getEntityId() === $b->getEntityId()) && ($a->getEntityTypeId() === $b->getEntityTypeId());
	}

	public function getFirst(): ?ItemIdentifier
	{
		if (!$this->isEmpty())
		{
			return $this->identifiers[0];
		}

		return null;
	}

	public function contains(ItemIdentifier $identifier): bool
	{
		foreach ($this->identifiers as $item)
		{
			if ($this->equals($item, $identifier))
			{
				return true;
			}
		}

		return false;
	}

	public function getDiff(Bindings $bindings): self
	{
		$diff = new self();
		foreach ($this->identifiers as $identifier)
		{
			if (!$bindings->contains($identifier))
			{
				$diff->add($identifier);
			}
		}

		return $diff;
	}

	public function getCount(): int
	{
		return count($this->identifiers);
	}
	public function isEquals(Bindings $bindings): bool
	{
		if ($this->getCount() !== $bindings->getCount())
		{
			return false;
		}

		foreach ($this->identifiers as $identifier)
		{
			if (!$bindings->contains($identifier))
			{
				return false;
			}
		}

		return true;
	}
}