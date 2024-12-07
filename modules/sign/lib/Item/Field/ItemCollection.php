<?php

namespace Bitrix\Sign\Item\Field;

use ArrayIterator;
use Bitrix\Sign\Contract;
use Countable;
use Iterator;

class ItemCollection implements Contract\ItemCollection, Contract\Item, Iterator, Countable
{
	private array $items;
	/** @var ArrayIterator<Item> */
	private ArrayIterator $iterator;

	public function __construct(Item ...$items)
	{
		$this->items = $items;
		$this->iterator = new ArrayIterator($this->items);
	}

	public function add(Item $item): static
	{
		$this->items[] = $item;

		return $this;
	}

	public function clear(): static
	{
		$this->items = [];

		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function current(): ?Item
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

	public function getFirst(): ?Item
	{
		return $this->items[0] ?? null;
	}

	public function findFirst(\Closure $rule): ?Item
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