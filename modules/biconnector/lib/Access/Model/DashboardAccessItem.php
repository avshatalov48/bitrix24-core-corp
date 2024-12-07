<?php

namespace Bitrix\BIConnector\Access\Model;

use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\Main\Access\AccessibleItem;

final class DashboardAccessItem implements AccessibleItem
{
	private int $id;
	private ?string $type = null;
	private ?int $ownerId = null;

	/**
	 * @param int $id
	 */
	public function __construct(int $id)
	{
		$this->id = $id;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): ?string
	{
		return $this->type;
	}

	public function getOwnerId(): ?int
	{
		return $this->ownerId;
	}

	/**
	 * Creates Access item from dashboard id to use in Access check.
	 * If Model/Dashboard object is available use createFromEntity method to avoid unnessesary DB queries.
	 * @see self::createFromEntity
	 *
	 * @param int $itemId Dashboard id.
	 *
	 * @return self
	 */
	public static function createFromId(int $itemId): self
	{
		$ormObject = SupersetDashboardTable::getById($itemId)->fetchObject();
		if (!$ormObject)
		{
			return new self($itemId);
		}

		return self::createFromEntity(new Dashboard($ormObject));
	}

	/**
	 * Creates Access item from dashboard fields to use in Access check.
	 *
	 * @param array{ID: int, TYPE: string, OWNER_ID: string} $fields Fields: ID, TYPE, OWNER_ID.
	 *
	 * @return self
	 */
	public static function createFromArray(array $fields): self
	{
		$accessItem = new self(
			(int)($fields['ID'] ?? 0)
		);
		$accessItem->type = $fields['TYPE'] ?? null;
		$accessItem->ownerId = $fields['OWNER_ID'] ?? null;

		return $accessItem;
	}

	/**
	 * Creates Access item from Model/Dashboard to use in Access check.
	 *
	 * @param Dashboard $dashboard Dashboard entity.
	 *
	 * @return self
	 */
	public static function createFromEntity(Dashboard $dashboard): self
	{
		$accessItem = new self(
			$dashboard->getId()
		);
		$accessItem->type = $dashboard->getType();
		$accessItem->ownerId = $dashboard->getField('OWNER_ID');

		return $accessItem;
	}
}
