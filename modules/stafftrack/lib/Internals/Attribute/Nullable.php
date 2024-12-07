<?php

namespace Bitrix\StaffTrack\Internals\Attribute;

use Attribute;

#[Attribute]
class Nullable implements CheckInterface
{
	public function check(mixed $value): bool
	{
		return $value === null;
	}
}