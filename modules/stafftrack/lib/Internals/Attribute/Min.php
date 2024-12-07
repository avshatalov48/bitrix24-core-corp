<?php

namespace Bitrix\StaffTrack\Internals\Attribute;

use Attribute;

#[Attribute]
class Min implements CheckInterface
{
	public function __construct(public int $min)
	{
	}

	public function check(mixed $value): bool
	{
		return (int)$value >= $this->min;
	}
}