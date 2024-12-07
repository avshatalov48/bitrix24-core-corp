<?php

namespace Bitrix\Sign\Item\Field;

use Bitrix\Sign\Item\Field;
use Bitrix\Sign\Contract;

class FieldValuePair implements Contract\Item
{
	public function __construct(
		public Field $field,
		public Value $value,
	)
	{
	}
}