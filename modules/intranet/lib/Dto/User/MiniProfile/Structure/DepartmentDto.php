<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Dto\User\MiniProfile\Structure;

class DepartmentDto implements \JsonSerializable
{
	public function __construct(
		public int $id,
		public string $title,
		public ?int $parentId,
		public int $employeeCount,
		/** @type array<int> */
		public array $headIds,
	) {}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
			'parentId' => $this->parentId,
			'employeeCount' => $this->employeeCount,
			'headIds' => $this->headIds,
		];
	}
}
