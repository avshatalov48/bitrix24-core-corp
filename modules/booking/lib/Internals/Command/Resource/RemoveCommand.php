<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Resource;

use Bitrix\Booking\Internals\Command\CommandInterface;

class RemoveCommand implements CommandInterface
{
	public function __construct(
		public readonly int $id,
		public readonly int $removedBy,
	)
	{

	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'removedBy' => $this->removedBy,
		];
	}
}
