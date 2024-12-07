<?php

namespace Bitrix\Sign\Item\Blank;

use Bitrix\Sign\Contract;
use Countable;
use Iterator;

class ResourceCollection implements Contract\Item, Contract\ItemCollection, Iterator, Countable
{
	private array $items;

	/** @var \ArrayIterator<Resource> */
	private \ArrayIterator $iterator;

	public function __construct(Resource ...$items)
	{
		$this->items = $items;
		$this->iterator = new \ArrayIterator($this->items);
	}

	public function add(Resource $item): ResourceCollection
	{
		$this->items[] = $item;

		return $this;
	}

	public function clear(): ResourceCollection
	{
		$this->items = [];

		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function current(): ?Resource
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

	public function getFirst(): ?Resource
	{
		return $this->items[0] ?? null;
	}

	/**
	 * @param \Closure(Resource): bool $rule
	 * @return ResourceCollection
	 */
	public function filter(\Closure $rule): ResourceCollection
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
	 * @param \Closure(Resource): bool $rule
	 * @return Resource|null
	 */
	final public function findFirst(\Closure $rule): ?Resource
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

	final public function findFirstByParty(int $party): ?Resource
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
