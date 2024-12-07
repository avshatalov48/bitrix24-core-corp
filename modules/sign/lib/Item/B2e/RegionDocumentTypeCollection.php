<?php

namespace Bitrix\Sign\Item\B2e;

use Bitrix\Sign\Contract\Item;
use Bitrix\Sign\Contract\ItemCollection;

class RegionDocumentTypeCollection implements Item, ItemCollection, \Iterator, \Countable
{
	/** @var \ArrayIterator<RegionDocumentType> */
	private \ArrayIterator $iterator;

	private array $items;

	public function __construct(RegionDocumentType ...$items)
	{
		$this->items = $items;
		$this->iterator = new \ArrayIterator($items);
	}

	public function add(RegionDocumentType $item): self
	{
		$this->iterator->append($item);

		return $this;
	}

	/**
	 * @return array<RegionDocumentType>
	 */
	public function all(): array
	{
		return $this->iterator->getArrayCopy();
	}


	public function toArray(): array
	{
		return array_map(fn(RegionDocumentType $type) => $type->toArray(), $this->all());
	}

	public function getIterator(): \ArrayIterator
	{
		return $this->iterator;
	}

	public function count(): int
	{
		return $this->getIterator()->count();
	}

	public function current(): ?RegionDocumentType
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

}
