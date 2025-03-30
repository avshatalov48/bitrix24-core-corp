<?php

namespace Bitrix\Sign\Type\Member\Notification;

use Bitrix\Sign\Contract\Item\IntModelValue;

enum ReminderType: string implements IntModelValue
{
	case NONE = 'none';
	case ONCE_PER_DAY = 'oncePerDay';
	case TWICE_PER_DAY = 'twicePerDay';
	case THREE_TIMES_PER_DAY = 'threeTimesPerDay';

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
			self::NONE => 0,
			self::ONCE_PER_DAY => 1,
			self::TWICE_PER_DAY => 2,
			self::THREE_TIMES_PER_DAY => 3,
		};
	}
}