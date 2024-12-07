<?php

namespace Bitrix\Tasks\Internals\Attribute;

use Attribute;

#[Attribute]
class ExpectedNumeric implements CheckInterface, ErrorMessageInterface
{
	public function check(mixed $value): bool
	{
		if (!is_array($value))
		{
			return false;
		}

		foreach ($value as $item)
		{
			$isNumeric = is_scalar($item) && (int)$item > 0;
			if (!$isNumeric)
			{
				return false;
			}
		}

		return true;
	}

	public function getError(string $field): string
	{
		return "'{$field}': field's value must be numeric";
	}
}