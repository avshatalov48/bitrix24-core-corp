<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\DelayedTask\Data;

use Bitrix\Booking\Internals\Service\DelayedTask\DelayedTaskType;

class ResourceCalendarDataChanged implements DataInterface
{
	private DelayedTaskType $type = DelayedTaskType::ResourceCalendarDataChanged;

	public function __construct(
		public readonly int $resourceId,
		public readonly bool $diffResult,
	)
	{
	}

	public function getType(): DelayedTaskType
	{
		return $this->type;
	}

	public function toArray(): array
	{
		return [
			'resourceId' => $this->resourceId,
			'diffResult' => $this->diffResult,
		];
	}

	public static function mapFromArray(array $params): self
	{
		return new self(
			resourceId: $params['resourceId'],
			diffResult: (bool)$params['diffResult'],
		);
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
