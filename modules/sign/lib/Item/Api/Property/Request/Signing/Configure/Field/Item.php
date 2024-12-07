<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure\Field;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Field;

class Item implements Contract\Item
{
	public function __construct(
		public string $id,
		public string $value,
	)
	{
	}

	public static function createFromFieldItem(Field\Item $item): static
	{
		return new static(
			$item->id,
			$item->value,
		);
	}
}