<?php

namespace Bitrix\Tasks\Flow\Attribute;

use Attribute;
use Bitrix\Tasks\Internals\Attribute\CheckInterface;

#[Attribute]
class AccessCode implements CheckInterface
{
	public function check(mixed $value): bool
	{
		if (!is_string($value))
		{
			return false;
		}

		// special case :))))
		if ($value === 'UA')
		{
			return true;
		}

		return \Bitrix\Main\Access\AccessCode::isValid($value);
	}
}