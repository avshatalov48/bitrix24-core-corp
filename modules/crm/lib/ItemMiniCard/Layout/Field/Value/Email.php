<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field\Value;

use Bitrix\Main\Type\Contract\Arrayable;
use JsonSerializable;

final class Email implements Arrayable, JsonSerializable
{
	public function __construct(
		public string $value,
		public int $ownerTypeId,
		public int $ownerId,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'value' => $this->value,
			'ownerTypeId' => $this->ownerTypeId,
			'ownerId' => $this->ownerId,
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
