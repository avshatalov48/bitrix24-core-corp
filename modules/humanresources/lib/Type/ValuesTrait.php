<?php

namespace Bitrix\HumanResources\Type;

trait ValuesTrait
{
	public static function values(): array
	{
		return array_column(self::cases(), 'name');
	}

	public static function isValid(mixed $value): bool
	{
		return in_array($value, self::values(), true);
	}
}