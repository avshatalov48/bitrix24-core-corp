<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Field\Fill;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Api\Property\Request\Field\Fill\Value;

class FieldValuesCollection implements Contract\ItemCollection, Contract\Item
{
	/** @var Value\BaseFieldValue[] $items  */
	public array $items;

	public function __construct(Value\BaseFieldValue ...$items)
	{
		$this->items = $items;
	}

	public function addItem(Value\BaseFieldValue $item): self
	{
		$this->items[] = $item;
		return $this;
	}

	public function toArray(): array
	{
		$array = [];

		foreach ($this->items as $item)
		{
			if ($item instanceof Value\StringFieldValue)
			{
				$array[] = $item->value;
			}
			else
			{
				$array[] = $item;
			}
		}
		return $array;
	}

	public function getFirst(): ?Value\BaseFieldValue
	{
		foreach ($this->items as $item)
		{
			return $item;
		}

		return null;
	}
}