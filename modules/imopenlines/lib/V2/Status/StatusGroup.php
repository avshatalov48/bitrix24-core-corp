<?php

namespace Bitrix\ImOpenLines\V2\Status;

enum StatusGroup: string
	implements \JsonSerializable
{
	case NEW = 'NEW';
	case WORK = 'WORK';
	case ANSWERED = 'ANSWERED';

	public static function getFromNumericalCode(int $code): StatusGroup
	{
		return match (true)
		{
			$code < 10 => self::NEW,
			$code >= 10 && $code < 40 => self::WORK,
			$code >= 40 => self::ANSWERED,
		};
	}

	public function getLowerBorder(): int
	{
		return match ($this)
		{
			self::NEW => 0,
			self::WORK => 10,
			self::ANSWERED => 40,
		};
	}

	public function getUpperBorder(): ?int
	{
		return match ($this)
		{
			self::NEW => 9,
			self::WORK => 39,
			self::ANSWERED => null,
		};
	}

	public function jsonSerialize(): mixed
	{
		return $this->name;
	}
}
