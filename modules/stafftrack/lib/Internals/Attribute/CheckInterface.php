<?php

namespace Bitrix\StaffTrack\Internals\Attribute;

interface CheckInterface
{
	public function check(mixed $value): bool;
}