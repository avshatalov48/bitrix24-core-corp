<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Field\Fill;

use Bitrix\Sign\Contract;

class MemberFieldsCollection implements Contract\ItemCollection, Contract\Item
{
	/** @var MemberFields[]  */
	public array $items;

	public function __construct(MemberFields ...$items)
	{
		$this->items = $items;
	}

	public function addItem(MemberFields $item): self
	{
		$this->items[] = $item;
		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}
}