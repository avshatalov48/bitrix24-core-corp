<?php

namespace Bitrix\Booking\Entity;

trait ValuesTrait
{
	public static function isValid(mixed $value): bool
	{
		return in_array($value, self::values(), true);
	}

	public static function values(): array
	{
		return array_column(self::cases(), 'value');
	}
}