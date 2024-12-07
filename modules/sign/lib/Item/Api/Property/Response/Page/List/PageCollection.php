<?php

namespace Bitrix\Sign\Item\Api\Property\Response\Page\List;

use Bitrix\Sign\Contract;

class PageCollection implements Contract\ItemCollection, Contract\Item
{
	/** @var Page[] */
	private array $items;

	public function __construct(Page ...$items)
	{
		$this->items = $items;
	}

	public function addItem(Page $item): self
	{
		$this->items[] = $item;
		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}
}