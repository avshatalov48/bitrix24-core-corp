<?php

namespace Bitrix\Tasks\Internals\Attribute;

use Attribute;

#[Attribute]
class Range implements CheckInterface, ErrorMessageInterface
{
	public function __construct(
		public int $min,
		public int $max
	)
	{

	}

	public function check(mixed $value): bool
	{
		if (!(new Min($this->min))->check($value))
		{
			return false;
		}

		return (new Max($this->max))->check($value);
	}

	public function getError(string $field): string
	{
		return "'{$field}': field's value must be greater than {$this->min} and less than {$this->max}";
	}
}