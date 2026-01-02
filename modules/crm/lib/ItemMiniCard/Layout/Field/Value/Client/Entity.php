<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field\Value\Client;

use Bitrix\Main\Type\Contract\Arrayable;
use JsonSerializable;

final class Entity implements JsonSerializable, Arrayable
{
	public function __construct(
		public int $entityTypeId,
		public int $entityId,
		public int $ownerTypeId,
		public int $ownerId,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'entityTypeId' => $this->entityTypeId,
			'entityId' => $this->entityId,
			'ownerTypeId' => $this->ownerTypeId,
			'ownerId' => $this->ownerId,
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
