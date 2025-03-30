<?php

namespace Bitrix\Sign\Type\Template;

use Bitrix\Sign\Contract\Item\IntModelValue;
use Bitrix\Sign\Type\ValuesTrait;

enum Visibility: string implements IntModelValue
{
	case VISIBLE = 'visible';
	case INVISIBLE = 'invisible';

	use ValuesTrait;

	public static function tryFromInt(int $status): ?self
	{
		$cases = self::getAll();
		foreach ($cases as $case)
		{
			if ($case->toInt() === $status)
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
			self::VISIBLE => 0,
			self::INVISIBLE => 1,
		};
	}

	public static function fromString(string $visibility): ?self
	{
		return match ($visibility)
		{
			self::VISIBLE->value => self::VISIBLE,
			self::INVISIBLE->value => self::INVISIBLE,
			default => null,
		};
	}
}
