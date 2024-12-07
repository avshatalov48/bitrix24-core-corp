<?php

namespace Bitrix\Tasks\Internals\Attribute;

use Attribute;

#[Attribute]
class NotEmpty implements CheckInterface, ErrorMessageInterface
{
	public function check(mixed $value): bool
	{
		return !empty($value);
	}

	public function getError(string $field): string
	{
		return "'{$field}': field's value must not be empty";
	}
}