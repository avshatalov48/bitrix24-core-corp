<?php

namespace Bitrix\HumanResources\Trait;

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

	public static function fromName(string $name): ?self
	{
		return self::from(
			array_column(self::cases(), 'value', 'name')[$name] ?? null
		);
	}
}