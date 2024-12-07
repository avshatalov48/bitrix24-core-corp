<?php

namespace Bitrix\StaffTrack\Item;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\StaffTrack\Item\Collection\DepartmentCollection;

class User implements Arrayable
{
	public function __construct(
		public int $id,
		public string $name,
		public string $avatar,
		public string $workPosition,
		public ?string $hash = null,
		public ?DepartmentCollection $departments = null,
		public ?bool $isAdmin = false,
	) {}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'avatar' => $this->avatar,
			'workPosition' => $this->workPosition,
			'hash' => $this->hash,
			'departments' => $this->departments?->toArray(),
			'isAdmin' => $this->isAdmin,
		];
	}
}