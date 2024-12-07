<?php

namespace Bitrix\StaffTrack\Internals\Attribute;

use Attribute;

#[Attribute]
class NotNull implements CheckInterface
{
	public function check(mixed $value): bool
	{
		return $value !== null;
	}
}