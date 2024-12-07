<?php

namespace Bitrix\Sign\Item\Field;

use Bitrix\Sign\Contract;

class ValueAddress implements Contract\Item
{
	public function __construct(
		public ?string $country = null,
		public ?string $city = null,
	){}
}