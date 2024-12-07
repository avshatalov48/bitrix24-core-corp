<?php

namespace Bitrix\Sign\Item;

use Bitrix\Sign\Contract\ItemCollection;
use Bitrix\Sign\Helper\IterationHelper;

class BlockCollection implements ItemCollection, \Iterator, \Countable
{
	private array $items;
	/** @var \ArrayIterator<Block> */
	private \ArrayIterator $iterator;

	public function __construct(Block ...$items)
	{
		$this->items = $items;
		$this->iterator = new \ArrayIterator($this->items);
	}

	public function add(Block $item): BlockCollection
	{
		$this->items[] = $item;

		return $this;
	}

	public function clear(): BlockCollection
	{
		$this->items = [];

		return $this;
	}

	public function getById(int $id): ?Block
	{
		foreach ($this->items as $item)
		{
			if ($item->id === $id)
			{
				return $item;
			}
		}

		return null;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function current(): ?Block
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

	public function all(\Closure $rule): bool
	{
		return IterationHelper::all($this->items, $rule);
	}

	public function filter(\Closure $rule): BlockCollection
	{
		$filtered = array_filter($this->items, $rule);
		$collection = new BlockCollection();

		foreach ($filtered as $block)
		{
			$collection->add(clone $block);
		}

		return $collection;
	}

	public function filterExcludeParty(int $party): BlockCollection
	{
		return $this->filter(static fn(Block $block) => $block->party !== $party);
	}

	public function filterExcludeRole(string $role): BlockCollection
	{
		return $this->filter(static fn(Block $block) => $block->role !== $role);
	}

	public function filterByParty(int $party): BlockCollection
	{
		return $this->filter(static fn(Block $block) => $block->party === $party);
	}

	public function filterByRole(string $role): BlockCollection
	{
		return $this->filter(static fn(Block $block) => $block->role === $role);
	}

	/**
	 * @return array<int>
	 */
	public function getIds(): iterable
	{
		$result = [];

		foreach ($this as $item)
		{
			$result[] = $item->id;
		}

		return $result;
	}
}