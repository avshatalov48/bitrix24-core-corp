<?php

namespace Bitrix\Tasks\Internals\Attribute;

use Attribute;

#[Attribute]
class PositiveNumber implements CheckInterface, ErrorMessageInterface
{
	public function check(mixed $value): bool
	{
		return (new Min(1))->check($value);
	}

	public function getError(string $field): string
	{
		return "'{$field}': field's value must be greater than 0";
	}
}