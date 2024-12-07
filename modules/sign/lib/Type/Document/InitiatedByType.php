<?php

namespace Bitrix\Sign\Type\Document;

use Bitrix\Sign\Type\ValuesTrait;

enum InitiatedByType: string
{
	use ValuesTrait;

	case COMPANY = 'company';
	case EMPLOYEE = 'employee';

	public function toInt(): int
	{
		return match ($this) {
			self::COMPANY => 0,
			self::EMPLOYEE => 1,
		};
	}

	public static function tryFromInt(int $type): ?self
	{
		foreach (self::cases() as $case)
		{
			if ($case->toInt() === $type)
			{
				return $case;
			}
		}

		return null;
	}
}
