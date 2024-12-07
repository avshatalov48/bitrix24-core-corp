<?php

namespace Bitrix\Sign\Item\Field;

use Bitrix\Sign\Contract;

class Item implements Contract\Item
{
	public function __construct(
		public string $id,
		public string $value,
	)
	{
	}
}