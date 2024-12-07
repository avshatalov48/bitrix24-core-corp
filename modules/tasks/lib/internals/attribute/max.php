<?php

namespace Bitrix\Tasks\Internals\Attribute;

use Attribute;

#[Attribute]
class Max implements CheckInterface, ErrorMessageInterface
{
	public function __construct(
		public int $max
	)
	{

	}

	public function check(mixed $value): bool
	{
		return $value <= $this->max;
	}

	public function getError(string $field): string
	{
		return "'{$field}': field's value must be less than {$this->max}";
	}
}