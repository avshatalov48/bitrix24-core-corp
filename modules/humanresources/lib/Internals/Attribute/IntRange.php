<?php

namespace Bitrix\HumanResources\Internals\Attribute;

use Attribute;
use Bitrix\HumanResources\Contract\Attribute\Validator;

#[Attribute]
class IntRange implements Validator
{
	public function __construct(
		private readonly ?int $min = null,
		private readonly ?int $max = null,
	) {}

	public function validate(mixed $value): bool
	{
		$value = (int)$value;
		if ($this->min !== null && $value < $this->min)
		{
			return false;
		}

		if ($this->max !== null && $value > $this->max)
		{
			return false;
		}

		return true;
	}
}