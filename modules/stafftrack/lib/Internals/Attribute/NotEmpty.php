<?php

namespace Bitrix\StaffTrack\Internals\Attribute;

use Attribute;

#[Attribute]
class NotEmpty implements CheckInterface
{
	public function check(mixed $value): bool
	{
		return !empty($value);
	}
}