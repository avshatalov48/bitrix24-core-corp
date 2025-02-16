<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\ResourceType;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Command\CommandInterface;

class UpdateCommand implements CommandInterface
{
	public function __construct(
		public readonly int $updatedBy,
		public readonly Entity\ResourceType\ResourceType $resourceType,
		public readonly Entity\Slot\RangeCollection|null $rangeCollection,
	)
	{

	}

	public function toArray(): array
	{
		return [
			'resourceType' => $this->resourceType->toArray(),
			'updatedBy' => $this->updatedBy,
			'ranges' => $this->rangeCollection?->toArray(),
		];
	}
}
