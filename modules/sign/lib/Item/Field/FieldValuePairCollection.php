<?php

namespace Bitrix\Sign\Item\Field;

use Bitrix\Sign\Contract\ItemCollection;

class FieldValuePairCollection implements ItemCollection
{
	private array $items;

	public function __construct(FieldValuePair ...$items)
	{
		$this->items = $items;
	}

	public function add(FieldValuePair $item): static
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

	public function isEmpty(): bool
	{
		return empty($this->items);
	}
}