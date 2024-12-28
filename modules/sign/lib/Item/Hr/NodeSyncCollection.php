<?php

namespace Bitrix\Sign\Item\Hr;

use Bitrix\Sign\Contract\ItemCollection;

final class NodeSyncCollection implements ItemCollection, \IteratorAggregate, \Countable
{
	/** @var \ArrayIterator<NodeSync> */
	private \ArrayIterator $iterator;

	public function __construct(NodeSync ...$items)
	{
		$this->iterator = new \ArrayIterator($items);
	}

	public function add(NodeSync $item): NodeSyncCollection
	{
		$this->iterator->append($item);

		return $this;
	}

	public function toArray(): array
	{
		return $this->iterator->getArrayCopy();
	}

	public function getIterator(): \ArrayIterator
	{
		return $this->iterator;
	}

	public function count(): int
	{
		return $this->getIterator()->count();
	}
}
