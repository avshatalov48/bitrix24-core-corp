<?php

namespace Bitrix\Crm\Integrity\Volatile\Type;

class NullField extends BaseField
{
	public function isNull(): bool
	{
		return true;
	}
}