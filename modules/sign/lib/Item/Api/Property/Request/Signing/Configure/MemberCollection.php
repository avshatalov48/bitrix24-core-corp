<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure;

use Bitrix\Sign\Contract;

class MemberCollection implements Contract\ItemCollection, Contract\Item
{
	/** @var Member[] */
	private array $items;

	public function __construct(Member ...$items)
	{
		$this->items = $items;
	}

	public function addItem(Member $item): self
	{
		$this->items[] = $item;
		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}
}