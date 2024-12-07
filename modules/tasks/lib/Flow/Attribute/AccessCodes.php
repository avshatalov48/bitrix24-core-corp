<?php

namespace Bitrix\Tasks\Flow\Attribute;

use Attribute;
use Bitrix\Tasks\Internals\Attribute\CheckInterface;

#[Attribute]
class AccessCodes implements CheckInterface
{
	public function check(mixed $value): bool
	{
		if (!is_countable($value))
		{
			return false;
		}

		foreach ($value as $item)
		{
			if (!(new AccessCode())->check($item))
			{
				return false;
			}
		}

		return true;
	}
}