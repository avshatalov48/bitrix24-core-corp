<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response\CalendarData;

use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Main\Type\Contract\Arrayable;

class ResourceDto implements Arrayable
{
	public function __construct(
		public readonly int $id,
		public readonly string $type,
		public readonly string $name,
		public readonly array $permissions,
	)
	{
	}

	public static function fromEntity(Resource $resource): self
	{
		return new self(
			id: $resource->getId(),
			type: $resource->getType()?->getName(),
			name: $resource->getName(),
			// where is no permissions for resources in the current implementation
			permissions: ['read' => true],
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'type' => $this->type,
			'name' => $this->name,
			'permissions' => $this->permissions,
		];
	}
}
