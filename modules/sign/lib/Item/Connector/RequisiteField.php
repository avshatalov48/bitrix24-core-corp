<?php

namespace Bitrix\Sign\Item\Connector;

use Bitrix\Main\Type\DateTime;

class RequisiteField
{
	public function __construct(
		public string $name,
		public string $label,
		public null|int|string|DateTime $value,
	)
	{
	}

	public static function isValueTypeSupported($value): bool
	{
		return is_string($value)
			|| is_int($value)
			|| $value instanceof DateTime
			|| $value === null
		;
	}
}