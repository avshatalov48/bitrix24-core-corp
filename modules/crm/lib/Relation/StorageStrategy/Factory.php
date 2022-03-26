<?php

namespace Bitrix\Crm\Relation\StorageStrategy;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class Factory extends \Bitrix\Crm\Relation\StorageStrategy
{
	/** @var Service\Factory */
	protected $childFactory;
	/** @var string */
	protected $parentIdFieldName;

	/**
	 * Factory constructor.
	 *
	 * @param Service\Factory $childFactory
	 * @param string $parentIdFieldName
	 *
	 * @throws ArgumentException
	 */
	public function __construct(Service\Factory $childFactory, string $parentIdFieldName)
	{
		if (!$childFactory->isFieldExists($parentIdFieldName))
		{
			throw new ArgumentException(
				'The provided field does not exist in ' . get_class($childFactory), 'fieldName'
			);
		}

		$this->childFactory = $childFactory;
		$this->parentIdFieldName = $parentIdFieldName;
	}

	/**
	 * @inheritDoc
	 */
	public function getParentElements(ItemIdentifier $child, int $parentEntityTypeId): array
	{
		$item = $this->childFactory->getItem($child->getEntityId());
		if (!$item)
		{
			return [];
		}

		$parentEntityId = $item->get($this->parentIdFieldName);
		if (!empty($parentEntityId))
		{
			return [new ItemIdentifier($parentEntityTypeId, $parentEntityId)];
		}

		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getChildElements(ItemIdentifier $parent, int $childEntityTypeId): array
	{
		$items = $this->childFactory->getItems([
			'select' => [Item::FIELD_NAME_ID],
			'filter' => [
				'=' . $this->parentIdFieldName => $parent->getEntityId(),
			],
		]);

		$children = [];
		foreach ($items as $item)
		{
			$children[] = ItemIdentifier::createByItem($item);
		}

		return $children;
	}

	/**
	 * @inheritDoc
	 */
	public function areItemsBound(ItemIdentifier $parent, ItemIdentifier $child): bool
	{
		$item = $this->childFactory->getItem($child->getEntityId());
		if (!$item)
		{
			return false;
		}

		return ($item->get($this->parentIdFieldName) === $parent->getEntityId());
	}

	/**
	 * @inheritDoc
	 */
	protected function createBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		return $this->editBinding($child, $parent->getEntityTypeId(), $parent->getEntityId());
	}

	/**
	 * @inheritDoc
	 */
	protected function deleteBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		return $this->editBinding($child, $parent->getEntityTypeId(), 0);
	}

	protected function editBinding(ItemIdentifier $child, int $parentEntityTypeId, int $value): Result
	{
		$item = $this->childFactory->getItem($child->getEntityId());
		if (!$item)
		{
			return (new Result())->addError(new Error('The child item does not exist: ' . $child));
		}

		$previousParentId = $item->get($this->parentIdFieldName);

		$item->set($this->parentIdFieldName, $value);

		$operation = $this->childFactory->getUpdateOperation($item);

		$operation->disableCheckAccess();

		$toExcludeFromTimeline = [];
		if ($previousParentId > 0)
		{
			$toExcludeFromTimeline[] = new ItemIdentifier($parentEntityTypeId, $previousParentId);
		}
		if ($value > 0)
		{
			$toExcludeFromTimeline[] = new ItemIdentifier($parentEntityTypeId, $value);
		}
		$operation->excludeItemsFromTimelineRelationEventsRegistration($toExcludeFromTimeline);

		return $operation->launch();
	}

	protected function replaceBindings(ItemIdentifier $fromItem, ItemIdentifier $toItem): Result
	{
		$childItems = $this->childFactory->getItems([
			'select' => [Item::FIELD_NAME_ID],
			'filter' => [
				'=' . $this->parentIdFieldName => $fromItem->getEntityId(),
			],
		]);

		$result = new Result();

		foreach ($childItems as $childItem)
		{
			$childItem->set($this->parentIdFieldName, $toItem->getEntityId());
			$saveResult = $childItem->save(false);
			if (!$saveResult->isSuccess())
			{
				$result->addErrors($saveResult->getErrors());
			}
		}

		return $result;
	}
}
