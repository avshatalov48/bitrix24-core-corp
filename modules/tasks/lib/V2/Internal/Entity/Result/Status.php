<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Result;

use Bitrix\Tasks\Internals\Task\Result\ResultTable;

enum Status: string
{
	case Open = 'open';
	case Closed = 'closed';

	public function getRaw(): int
	{
		return match ($this)
		{
			self::Open => ResultTable::STATUS_OPENED,
			self::Closed => ResultTable::STATUS_CLOSED,
		};
	}

	public static function fromRaw(int $value): self
	{
		return match ($value) {
			ResultTable::STATUS_OPENED => self::Open,
			ResultTable::STATUS_CLOSED => self::Closed,
		};
	}
}
