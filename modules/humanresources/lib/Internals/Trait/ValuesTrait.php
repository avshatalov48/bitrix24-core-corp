<?php

namespace Bitrix\HumanResources\Internals\Trait;

trait ValuesTrait
{
	public static function names(): array
	{
		$values = array_column(self::cases(), 'value');

		if ($values)
		{
			return $values;
		}

		return array_column(self::cases(), 'name');
	}

	public static function values(): array
	{
		return array_column(self::cases(), 'value');
	}

	public static function isValid(mixed $value): bool
	{
		return in_array($value, self::names(), true);
	}

	public static function fromName(string $name): ?self
	{
		return self::from(
			array_column(self::cases(), 'value', 'name')[$name] ?? null
		);
	}
}