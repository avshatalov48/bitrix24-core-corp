<?php

namespace Bitrix\Tasks\Internals\Attribute;

interface CheckInterface
{
	public function check(mixed $value): bool;
}