<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Favorites;

use Bitrix\Booking\Internals\Command\CommandInterface;

class AddCommand implements CommandInterface
{
	public function __construct(
		public readonly int $managerId,
		public readonly array $resourcesIds,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'managerId' => $this->managerId,
			'resourcesIds' => $this->resourcesIds,
		];
	}
}
