<?php

namespace Bitrix\HumanResources\Internals\Attribute;

use Attribute;
use Bitrix\HumanResources\Contract\Attribute\Validator;

#[Attribute]
class Required implements Validator
{
	public function validate(mixed $value): bool
	{
		return isset($value);
	}
}