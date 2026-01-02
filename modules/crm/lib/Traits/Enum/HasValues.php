<?php

namespace Bitrix\Crm\Traits\Enum;

use BackedEnum;

/**
 * @implements BackedEnum
 */
trait HasValues
{
	/**
	 * @return int[]|string[]
	 */
	public static function values(): array
	{
		return array_map(static fn (BackedEnum $enum) => $enum->value, static::cases());
	}
}
