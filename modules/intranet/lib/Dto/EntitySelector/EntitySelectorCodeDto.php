<?php

namespace Bitrix\Intranet\Dto\EntitySelector;

class EntitySelectorCodeDto implements \JsonSerializable
{
	public function __construct(
		public bool $isAllUser,
		/** @var int[] $userIds */
		public array $userIds = [],
		/** @var int[] $departmentIds */
		public array $departmentIds = [],
		/** @var int[] $departmentWithAllChildIds */
		public array $departmentWithAllChildIds = [],
	)
	{
	}

	public function jsonSerialize(): mixed
	{
		$value = array_merge(
			array_map(fn (int $userId) => 'U' . $userId, $this->userIds),
			array_map(fn (int $departmentId) => 'DR' . $departmentId, $this->departmentIds),
			array_map(fn (int $departmentWithAllChildId) => 'D' . $departmentWithAllChildId, $this->departmentWithAllChildIds),
		);

		if ($this->isAllUser)
		{
			$value[] = 'UA';
		}

		return $value;
	}
}
