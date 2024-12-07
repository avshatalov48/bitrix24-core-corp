<?php

namespace Bitrix\Sign\Item\Field;

use ArrayIterator;
use Bitrix\Sign\Contract\ItemCollection;

class ValueCollection implements ItemCollection, \Iterator, \Countable
{
	private array $items;
	/** @var ArrayIterator<Value> */
	private ArrayIterator $iterator;

	public function __construct(Value ...$items)
	{
		$this->items = $items;
		$this->iterator = new ArrayIterator($this->items);
	}

	public function add(Value $item): static
	{
		$this->items[] = $item;

		return $this;
	}

	public function clear(): static
	{
		$this->items = [];

		return $this;
	}

	public function listText(): array
	{
		$list = [];
		foreach ($this->items as $item)
		{
			if ($item->text !== '' && $item->text !== null)
			{
				$list[] = $item->text;
			}
		}
		return $list;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function filterByFieldId(int $fieldId): array
	{
		$items = [];
		foreach ($this->items as $item)
		{
			if ($item->fieldId === $fieldId)
			{
				$items[] = $item;
			}
		}

		return $items;
	}

	public function filter(\Closure $rule): ValueCollection
	{
		$collection = new ValueCollection();

		foreach ($this->items as $item)
		{
			if ($rule($item))
			{
				$collection->add($item);
			}
		}

		return $collection;
	}

	public function current(): ?Value
	{
		return $this->iterator->current();
	}

	public function next(): void
	{
		$this->iterator->next();
	}

	public function key(): int
	{
		return $this->iterator->key();
	}

	public function valid(): bool
	{
		return $this->iterator->valid();
	}

	public function rewind(): void
	{
		$this->iterator = new ArrayIterator($this->items);
	}

	public function count(): int
	{
		return count($this->items);
	}

	public function isEmpty(): bool
	{
		return empty($this->items);
	}

	public function getFirst(): ?Value
	{
		return $this->items[0] ?? null;
	}

	public function findFirst(\Closure $rule): ?Value
	{
		foreach ($this as $item)
		{
			if ($rule($item))
			{
				return $item;
			}
		}

		return null;
	}
}