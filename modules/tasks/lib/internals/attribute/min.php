<?php

namespace Bitrix\Tasks\Internals\Attribute;

use Attribute;

#[Attribute]
class Min implements CheckInterface, ErrorMessageInterface
{
	public function __construct(
		public int $min
	)
	{

	}

	public function check(mixed $value): bool
	{
		return $value >= $this->min;
	}

	public function getError(string $field): string
	{
		return "'{$field}': field's value must be greater than {$this->min}";
	}
}