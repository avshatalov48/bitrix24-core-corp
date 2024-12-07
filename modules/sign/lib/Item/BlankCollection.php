<?php

namespace Bitrix\Sign\Item;

use Bitrix\Sign\Contract\ItemCollection;

class BlankCollection implements ItemCollection
{
	private array $items;

	public function __construct(Blank ...$items)
	{
		$this->items = $items;
	}

	public function add(Blank $item): static
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
}