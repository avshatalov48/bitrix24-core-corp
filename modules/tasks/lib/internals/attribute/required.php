<?php

namespace Bitrix\Tasks\Internals\Attribute;

use Attribute;

#[Attribute]
class Required implements CheckInterface, ErrorMessageInterface
{
	public function check(mixed $value): bool
	{
		return isset($value);
	}

	public function getError(string $field): string
	{
		return "'{$field}': field's value must be set";
	}
}