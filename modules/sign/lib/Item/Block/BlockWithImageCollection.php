<?php

namespace Bitrix\Sign\Item\Block;

use Bitrix\Sign\Contract;

class BlockWithImageCollection implements Contract\ItemCollection, \Countable
{
	private array $items;

	public function __construct(BlockWithImage ...$items)
	{
		$this->items = $items;
	}

	public function add(BlockWithImage $item): BlockWithImageCollection
	{
		$this->items[] = $item;

		return $this;
	}

	public function clear(): BlockWithImageCollection
	{
		$this->items = [];

		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function count(): int
	{
		return count($this->items);
	}
}