<?php

namespace Bitrix\Sign\Item\Connector;

use ArrayIterator;
use Bitrix\Sign\Contract\ItemCollection;
use Bitrix\Sign\Contract\Item;
use Closure;
use Countable;
use Iterator;

class FieldCollection implements ItemCollection, Item, Iterator, Countable
{
	private array $items;
	/** @var ArrayIterator<Field> */
	private ArrayIterator $iterator;

	public function __construct(Field ...$items)
	{
		$this->items = $items;
		$this->iterator = new ArrayIterator($this->items);
	}

	public function clear(): FieldCollection
	{
		$this->items = [];

		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	/**
	 * @return string[]
	 */
	public function listNames(): array
	{
		return array_map(fn (Field $item) => $item->name, $this->items);
	}

	public function getFirstByName(string $name): ?Field
	{
		foreach ($this->items as $item)
		{
			if ($item->name === $name)
			{
				return $item;
			}
		}

		return null;
	}

	public function current(): ?Field
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

	public function getFirst(): ?Field
	{
		return $this->items[0] ?? null;
	}

	final public function findFirst(Closure $rule): ?Field
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

	final public function filter(Closure $rule): FieldCollection
	{
		$result = new FieldCollection();
		foreach ($this as $item)
		{
			if ($rule($item))
			{
				$result->add($item);
			}
		}

		return $result;
	}

	public function add(Field $item): FieldCollection
	{
		$this->items[] = $item;

		return $this;
	}
}