<?php

namespace Bitrix\HumanResources\Contract\Attribute;

interface Validator
{
	public function validate(mixed $value): bool;
}