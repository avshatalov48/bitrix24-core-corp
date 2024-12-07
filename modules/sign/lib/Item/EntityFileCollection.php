<?php

namespace Bitrix\Sign\Item;

use Bitrix\Sign\Contract;

class EntityFileCollection implements Contract\Item, Contract\ItemCollection, \Iterator, \Countable
{
	private array $items;

	/** @var \ArrayIterator<EntityFile> */
	private \ArrayIterator $iterator;

	public function __construct(EntityFile ...$items)
	{
		$this->items = $items;
		$this->iterator = new \ArrayIterator($this->items);
	}

	public function add(EntityFile $item): EntityFileCollection
	{
		$this->items[] = $item;

		return $this;
	}

	public function clear(): EntityFileCollection
	{
		$this->items = [];

		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function current(): ?EntityFile
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
		$this->iterator = new \ArrayIterator($this->items);
	}

	public function count(): int
	{
		return count($this->items);
	}

	public function isEmpty(): bool
	{
		return empty($this->items);
	}

	public function getFirst(): ?EntityFile
	{
		return $this->items[0] ?? null;
	}

	/**
	 * @param \Closure(EntityFile): bool $rule
	 * @return EntityFileCollection
	 */
	public function filter(\Closure $rule): EntityFileCollection
	{
		$result = new static();
		foreach ($this as $item)
		{
			if ($rule($item))
			{
				$result->add($item);
			}
		}

		return $result;
	}

	/**
	 * @param \Closure(EntityFile): bool $rule
	 * @return EntityFile|null
	 */
	final public function findFirst(\Closure $rule): ?EntityFile
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

	final public function findFirstByParty(int $party): ?EntityFile
	{
		foreach ($this as $item)
		{
			if ($item->party === $party)
			{
				return $item;
			}
		}

		return null;
	}

	/**
	 * @return array<?int>
	 */
	final public function getIds(): array
	{
		$result = [];
		foreach ($this as $member)
		{
			$result[] = $member->id;
		}

		return $result;
	}

	final public function sort(\Closure $rule): static
	{
		$result = $this->items;
		usort($result, $rule);

		return new static(...$result);
	}
}
