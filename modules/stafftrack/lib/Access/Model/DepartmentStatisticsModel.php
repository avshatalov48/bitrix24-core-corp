<?php

namespace Bitrix\StaffTrack\Access\Model;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Stafftrack\Integration\HumanResources\Structure;

class DepartmentStatisticsModel implements AccessibleItem
{
	protected static array $cache = [];

	public function __construct(
		protected int $departmentId,
		protected array $userIds,
	) {}

	public static function createFromId(int $itemId): AccessibleItem
	{
		if (empty(self::$cache[$itemId]))
		{
			self::$cache[$itemId] = self::createNew($itemId);
		}

		return self::$cache[$itemId];
	}

	public static function createNew(int $departmentId): self
	{
		$userIds = Structure::getInstance()->getDepartmentUserIds($departmentId);

		return new self(
			departmentId: $departmentId,
			userIds: $userIds,
		);
	}

	public function getId(): int
	{
		return $this->departmentId;
	}

	/**
	 * @return array<int>
	 */
	public function getUserIds(): array
	{
		return $this->userIds;
	}
}