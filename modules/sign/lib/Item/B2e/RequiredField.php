<?php

namespace Bitrix\Sign\Item\B2e;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Sign\Contract\Item;

class RequiredField implements Item, \JsonSerializable, Arrayable
{
	public function __construct(
		public string $type,
		public string $role,
	) {}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}

	public function toArray(): array
	{
		return [
			'type' => $this->type,
			'role' => $this->role,
		];
	}
}
