<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model\ResourceLinkedEntityData;

use Bitrix\Main\Type\Contract\Arrayable;

class CalendarDataRemindersDto implements Arrayable, \JsonSerializable
{
	public function __construct(
		public readonly string $type,
		public readonly int $count,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'type' => $this->type,
			'count' => $this->count,
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
