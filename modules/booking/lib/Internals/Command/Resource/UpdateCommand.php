<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Resource;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Command\CommandInterface;

class UpdateCommand implements CommandInterface
{
	public function __construct(
		public readonly int $updatedBy,
		public readonly Entity\Resource\Resource $resource,
	)
	{

	}

	public function toArray(): array
	{
		return [
			'resource' => $this->resource->toArray(),
			'updatedBy' => $this->updatedBy,
		];
	}
}
