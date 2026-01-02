<?php

namespace Bitrix\Sign\Type\Document;

use Bitrix\Sign\Contract\Item\IntModelValue;

enum ExternalDateCreateSourceType: string implements IntModelValue
{
	case MANUAL = 'manual';
	case HCMLINK = 'hcmlink';

	public static function tryFromInt(int $number): ?self
	{
		foreach (self::cases() as $case)
		{
			if ($case->toInt() === $number)
			{
				return $case;
			}
		}

		return null;
	}

	public function toInt(): int
	{
		return match ($this)
		{
			self::MANUAL => 0,
			self::HCMLINK => 1,
		};
	}
}