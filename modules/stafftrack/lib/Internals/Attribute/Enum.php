<?php

namespace Bitrix\StaffTrack\Internals\Attribute;

use Attribute;

#[Attribute]
class Enum implements CheckInterface
{
	public function __construct(public string $enumClass)
	{
	}

	public function check(mixed $value): bool
	{
		return in_array($value, array_column($this->enumClass::cases(), 'value'));
	}
}