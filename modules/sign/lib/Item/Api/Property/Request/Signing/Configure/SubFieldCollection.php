<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure;

use Bitrix\Sign\Contract;

class SubFieldCollection implements Contract\ItemCollection, Contract\Item
{
	/** @var SubField[] */
	private array $items;

	public function __construct(SubField ...$items)
	{
		$this->items = $items;
	}

	public function addItem(SubField $item): self
	{
		$this->items[] = $item;
		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}
}