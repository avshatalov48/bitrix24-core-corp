<?php

namespace Bitrix\Sign\Type;

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

	/**
	 * @return list<static>
	 */
	public static function getAll(): array
	{
		return static::cases();
	}
}