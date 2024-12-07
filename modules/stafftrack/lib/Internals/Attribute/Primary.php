<?php

namespace Bitrix\StaffTrack\Internals\Attribute;

use Attribute;

#[Attribute]
class Primary implements CheckInterface
{
	public function check(mixed $value): bool
	{
		return true;
	}
}