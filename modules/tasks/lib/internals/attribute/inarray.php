<?php

namespace Bitrix\Tasks\Internals\Attribute;

use Attribute;

#[Attribute]
class InArray implements CheckInterface, ErrorMessageInterface
{
	public function __construct(
		public array $validValues
	) {}

	public function check(mixed $value): bool
	{
		return in_array($value, $this->validValues, true);
	}

	public function getError(string $field): string
	{
		return "'{$field}': field's value has an invalid value";
	}
}