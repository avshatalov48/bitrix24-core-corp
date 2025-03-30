<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Journal;

class JournalEvent
{
	public function __construct(
		public readonly int $entityId,
		public readonly JournalType $type,
		public readonly array $data,
		public readonly int|null $id = null,
	)
	{

	}
}
