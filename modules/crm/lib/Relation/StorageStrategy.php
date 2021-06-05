<?php

namespace Bitrix\Crm\Relation;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

abstract class StorageStrategy
{
	/**
	 * Fetch ItemIdentifier objects that are parents to the provided child from the DB.
	 * Only parents with $parent->getEntityTypeId() === $parentEntityTypeId are returned
	 *
	 * @param ItemIdentifier $child
	 * @param int $parentEntityTypeId
	 *
	 * @return ItemIdentifier[]
	 */
	abstract public function getParentElements(ItemIdentifier $child, int $parentEntityTypeId): array;

	/**
	 * Fetch ItemIdentifier objects that are children to the provided parent from the DB.
	 * Only children with $child->getEntityTypeId() === $childEntityTypeId are returned
	 *
	 * @param ItemIdentifier $parent
	 * @param int $childEntityTypeId
	 *
	 * @return ItemIdentifier[]
	 */
	abstract public function getChildElements(ItemIdentifier $parent, int $childEntityTypeId): array;

	/**
	 * Returns true if the items are bound
	 *
	 * @param ItemIdentifier $parent
	 * @param ItemIdentifier $child
	 *
	 * @return bool
	 */
	abstract public function areItemsBound(ItemIdentifier $parent, ItemIdentifier $child): bool;

	/**
	 * Bind the provided items with each other
	 *
	 * @param ItemIdentifier $parent
	 * @param ItemIdentifier $child
	 *
	 * @return Result
	 */
	public function bindItems(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		if ($this->areItemsBound($parent, $child))
		{
			return (new Result())->addError(new Error(
					'The items are bound already',
					RelationManager::ERROR_CODE_BIND_ITEMS_ITEMS_ALREADY_BOUND
				));
		}

		return $this->createBinding($parent, $child);
	}

	/**
	 * Unbind the provided items
	 *
	 * @param ItemIdentifier $parent
	 * @param ItemIdentifier $child
	 *
	 * @return Result
	 */
	public function unbindItems(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		if (!$this->areItemsBound($parent, $child))
		{
			return (new Result())->addError(new Error(
					'The items are not bound',
					RelationManager::ERROR_CODE_UNBIND_ITEMS_ITEMS_NOT_BOUND
				));
		}

		return $this->deleteBinding($parent, $child);
	}

	/**
	 * Write the binding record into the DB
	 *
	 * @param ItemIdentifier $parent
	 * @param ItemIdentifier $child
	 *
	 * @return Result
	 */
	abstract protected function createBinding(ItemIdentifier $parent, ItemIdentifier $child): Result;

	/**
	 * Delete the binding record from the DB
	 *
	 * @param ItemIdentifier $parent
	 * @param ItemIdentifier $child
	 *
	 * @return Result
	 */
	abstract protected function deleteBinding(ItemIdentifier $parent, ItemIdentifier $child): Result;
}