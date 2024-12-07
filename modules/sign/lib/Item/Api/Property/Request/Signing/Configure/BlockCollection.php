<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure;

use Bitrix\Sign\Contract;

class BlockCollection implements Contract\ItemCollection, Contract\Item
{
	/** @var Block[] */
	private array $items;

	public function __construct(Block ...$items)
	{
		$this->items = $items;
	}

	public function addItem(Block $item): self
	{
		$this->items[] = $item;
		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}
}