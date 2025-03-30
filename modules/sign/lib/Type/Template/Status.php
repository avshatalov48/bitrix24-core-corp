<?php

namespace Bitrix\Sign\Type\Template;

use Bitrix\Main\SystemException;
use Bitrix\Sign\Contract\Item\IntModelValue;
use Bitrix\Sign\Type\ValuesTrait;

enum Status: string implements IntModelValue
{
	case NEW = 'new';
	case COMPLETED = 'completed';

	use ValuesTrait;

	public static function fromInt(int $status): self
	{
		return self::tryFromInt($status) ?? throw new SystemException('Unknown status');
	}

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
			self::NEW => 0,
			self::COMPLETED => 1,
		};
	}
}
