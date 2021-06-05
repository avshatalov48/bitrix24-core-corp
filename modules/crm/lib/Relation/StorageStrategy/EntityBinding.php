<?php

namespace Bitrix\Crm\Relation\StorageStrategy;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Relation\StorageStrategy;
use Bitrix\Main\Result;

class EntityBinding extends StorageStrategy
{
	/** @var callable */
	protected $getParentIds;
	/** @var callable */
	protected $getChildIds;
	/** @var callable */
	protected $bindParentIds;
	/** @var callable */
	protected $unbindParentIds;

	/**
	 * EntityBinding constructor.
	 *
	 * @param callable $getParentIds function(int $childEntityId): int[]
	 * @param callable $getChildIds function(int $parentEntityId): int[]
	 * @param callable $bindParentIds function(int $childEntityId, int[] $parentIds): void
	 * @param callable $unbindParentIds function(int $childEntityId, int[] $parentIds): void
	 */
	public function __construct(
		callable $getParentIds,
		callable $getChildIds,
		callable $bindParentIds,
		callable $unbindParentIds
	)
	{
		$this->getParentIds = $getParentIds;
		$this->getChildIds = $getChildIds;
		$this->bindParentIds = $bindParentIds;
		$this->unbindParentIds = $unbindParentIds;
	}

	/**
	 * @inheritDoc
	 */
	public function getParentElements(ItemIdentifier $child, int $parentEntityTypeId): array
	{
		$result = [];
		foreach ($this->getParentIdsForChild($child) as $parentId)
		{
			$result[] = new ItemIdentifier($parentEntityTypeId, $parentId);
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function getChildElements(ItemIdentifier $parent, int $childEntityTypeId): array
	{
		$result = [];
		foreach ($this->getChildIdsForParent($parent) as $childId)
		{
			$result[] = new ItemIdentifier($childEntityTypeId, $childId);
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function areItemsBound(ItemIdentifier $parent, ItemIdentifier $child): bool
	{
		foreach ($this->getParentIdsForChild($child) as $parentId)
		{
			if ($parentId === $parent->getEntityId())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param ItemIdentifier $child
	 *
	 * @return int[]
	 */
	protected function getParentIdsForChild(ItemIdentifier $child): array
	{
		return call_user_func($this->getParentIds, $child->getEntityId());
	}

	/**
	 * @param ItemIdentifier $parent
	 *
	 * @return int[]
	 */
	protected function getChildIdsForParent(ItemIdentifier $parent): array
	{
		return call_user_func($this->getChildIds, $parent->getEntityId());
	}

	/**
	 * @inheritDoc
	 */
	protected function createBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		call_user_func($this->bindParentIds, $child->getEntityId(), [$parent->getEntityId()]);

		return new Result();
	}

	/**
	 * @inheritDoc
	 */
	protected function deleteBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		call_user_func($this->unbindParentIds, $child->getEntityId(), [$parent->getEntityId()]);

		return new Result();
	}
}
