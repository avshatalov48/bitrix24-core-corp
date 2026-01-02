<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\DelayedTask\Data;

use Bitrix\Booking\Internals\Service\DelayedTask\Data\ResourceLinkedEntityDiff\ResourceLinkedEntityCollectionDiff;

class ResourceLinkedEntitiesChangedData
{
	public function __construct(
		public readonly int $resourceId,
		public readonly ResourceLinkedEntityCollectionDiff $diffResult,
	)
	{
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
			diffResult: ResourceLinkedEntityCollectionDiff::mapFromArray($params['diffResult'])
		);
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
