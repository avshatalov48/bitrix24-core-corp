<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure\Field;

use Bitrix\Sign\Contract;

class ItemCollection implements Contract\ItemCollection, Contract\Item
{
	/** @var Item[] */
	private array $items;

	public function __construct(Item ...$items)
	{
		$this->items = $items;
	}

	public function addItem(Item $item): self
	{
		$this->items[] = $item;
		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}
}