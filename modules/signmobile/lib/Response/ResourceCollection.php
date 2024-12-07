<?php

namespace Bitrix\SignMobile\Response;

use ArrayIterator;
use Bitrix\SignMobile\Contract\Response\ResourceCollectionContract;
use Bitrix\SignMobile\Contract\Response\ResourceContract;
use Iterator;

abstract class ResourceCollection implements ResourceCollectionContract
{
	/** @var ResourceContract[] */
	private array $items;
	private ArrayIterator $iterator;

	public function __construct(ResourceContract ...$items)
	{
		$this->items = $items;
		$this->iterator = new ArrayIterator($this->items);
	}

	public function add(ResourceContract $resource): static
	{
		$this->items[] = $resource;
		return $this;
	}

	public function toArray(): array
	{
		return array_map(fn (ResourceContract $resource) => $resource->toArray(), $this->items);
	}

	public function current(): ResourceContract
	{
		return $this->iterator->current();
	}

	public function next(): void
	{
		$this->iterator->next();
	}

	public function key(): int
	{
		return (int)$this->iterator->key();
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

	/**
	 * @return Iterator<int, ResourceContract>
	 */
	public function getIterator(): Iterator
	{
		return new ArrayIterator($this->toArray());
	}
}
