<?php

namespace Bitrix\StaffTrack\Item;

use Bitrix\Main\Type\Contract\Arrayable;

class Department implements Arrayable
{
	public function __construct(
		public int $id,
		public string $name,
	) {}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
		];
	}
}