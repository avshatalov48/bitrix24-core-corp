<?php

namespace Bitrix\Sign\Item;

use ArrayIterator;
use Bitrix\Sign\Contract;
use Countable;
use Iterator;

class UserCollection implements Contract\Item, Contract\ItemCollection, Iterator, Countable
{
	private array $items;
	/** @var ArrayIterator<User> */
	private ArrayIterator $iterator;

	public function __construct(User ...$items)
	{
		$this->items = $items;
		$this->iterator = new ArrayIterator($this->items);
	}

	public function add(User $item): static
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

	public function current(): ?User
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
}
