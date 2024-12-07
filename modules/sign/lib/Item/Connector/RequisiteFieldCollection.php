<?php

namespace Bitrix\Sign\Item\Connector;

use ArrayIterator;
use Bitrix\Sign\Contract\ItemCollection;
use Closure;
use Countable;
use Iterator;

class RequisiteFieldCollection implements ItemCollection, Iterator, Countable
{
	private array $items;
	/** @var ArrayIterator<RequisiteField> */
	private ArrayIterator $iterator;

	public function __construct(RequisiteField ...$items)
	{
		$this->items = $items;
		$this->iterator = new ArrayIterator($this->items);
	}

	public function clear(): RequisiteFieldCollection
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
		return array_map(fn (RequisiteField $item) => $item->name, $this->items);
	}

	public function getFirstByName(string $name): ?RequisiteField
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

	public function current(): ?RequisiteField
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

	public function getFirst(): ?RequisiteField
	{
		return $this->items[0] ?? null;
	}

	final public function findFirst(Closure $rule): ?RequisiteField
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

	final public function filter(Closure $rule): RequisiteFieldCollection
	{
		$result = new RequisiteFieldCollection();
		foreach ($this as $item)
		{
			if ($rule($item))
			{
				$result->add($item);
			}
		}

		return $result;
	}

	public function add(RequisiteField $item): RequisiteFieldCollection
	{
		$this->items[] = $item;

		return $this;
	}
}