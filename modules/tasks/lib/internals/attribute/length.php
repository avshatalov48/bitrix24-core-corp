<?php

namespace Bitrix\Tasks\Internals\Attribute;

use Attribute;

#[Attribute]
class Length implements CheckInterface, ErrorMessageInterface
{
	public function __construct(
		public int $min,
		public int $max
	)
	{

	}

	public function check(mixed $value): bool
	{
		if (!is_string($value))
		{
			return false;
		}

		$length = mb_strlen($value);

		return $this->min <= $length && $length <= $this->max;
	}

	public function getError(string $field): string
	{
		return "'{$field}': field's length must be greater than {$this->min} and less than {$this->max}";
	}
}