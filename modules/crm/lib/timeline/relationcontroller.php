<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;

class RelationController extends Controller
{
	protected function __construct()
	{
	}

	protected function __clone()
	{
	}

	/**
	 * @param ItemIdentifier $child
	 * @param array[] $fieldsInfo
	 * @param mixed[] $previousFields
	 * @param mixed[] $currentFields
	 * @param ItemIdentifier[] $itemsToIgnore
	 * @param int|null $authorId
	 */
	public function registerEventsByFieldsChange(
		ItemIdentifier $child,
		array $fieldsInfo,
		array $previousFields,
		array $currentFields,
		array $itemsToIgnore = [],
		?int $authorId = null
	): void
	{
		$difference = ComparerBase::compareEntityFields($previousFields, $currentFields);

		foreach ($fieldsInfo as $fieldName => $singleFieldInfo)
		{
			$parentEntityTypeId = (int)($singleFieldInfo['SETTINGS']['parentEntityTypeId'] ?? null);
			if (!\CCrmOwnerType::IsDefined($parentEntityTypeId))
			{
				continue;
			}

			if (!$difference->isChanged($fieldName))
			{
				continue;
			}

			$previousValue = (int)$difference->getPreviousValue($fieldName);
			if ($previousValue > 0)
			{
				$oldParent = new ItemIdentifier($parentEntityTypeId, $previousValue);

				// intentional not strict comparison. it doesn't matter here if objects are the same instance
				if (!in_array($oldParent, $itemsToIgnore, false))
				{
					$this->onItemsUnbind($oldParent, $child, $authorId);
				}
			}

			$currentValue = (int)$difference->getCurrentValue($fieldName);
			if ($currentValue > 0)
			{
				$newParent = new ItemIdentifier($parentEntityTypeId, $currentValue);

				// intentional not strict comparison. it doesn't matter here if objects are the same instance
				if (!in_array($newParent, $itemsToIgnore, false))
				{
					$this->onItemsBind($newParent, $child, $authorId);
				}
			}
		}
	}

	/**
	 * @param ItemIdentifier $child
	 * @param int $parentEntityTypeId
	 * @param array[] $previousBindings
	 * @param array[] $currentBindings
	 * @param ItemIdentifier[] $itemsToIgnore
	 * @param int|null $authorId
	 */
	public function registerEventsByBindingsChange(
		ItemIdentifier $child,
		int $parentEntityTypeId,
		array $previousBindings,
		array $currentBindings,
		array $itemsToIgnore = [],
		?int $authorId = null
	): void
	{
		[$bound, $unbound] = EntityBinding::prepareBoundAndUnboundEntities(
			$parentEntityTypeId,
			$previousBindings,
			$currentBindings
		);

		foreach (EntityBinding::prepareEntityIDs($parentEntityTypeId, $unbound) as $removedParentId)
		{
			$oldParent = new ItemIdentifier($parentEntityTypeId, $removedParentId);

			// intentional not strict comparison. it doesn't matter here if objects are the same instance
			if (!in_array($oldParent, $itemsToIgnore, false))
			{
				$this->onItemsUnbind($oldParent, $child, $authorId);
			}
		}

		foreach (EntityBinding::prepareEntityIDs($parentEntityTypeId, $bound) as $addedParentId)
		{
			$newParent = new ItemIdentifier($parentEntityTypeId, $addedParentId);

			// intentional not strict comparison. it doesn't matter here if objects are the same instance
			if (!in_array($newParent, $itemsToIgnore, false))
			{
				$this->onItemsBind($newParent, $child, $authorId);
			}
		}
	}

	public function onItemsBind(ItemIdentifier $parent, ItemIdentifier $child, ?int $authorId = null): void
	{
		$this->registerBindEvent($parent, $child, $authorId);
		$this->registerBindEvent($child, $parent, $authorId);
	}

	/**
	 * Register an unbind event in items' timelines
	 *
	 * @param ItemIdentifier $parent
	 * @param ItemIdentifier $child
	 * @param int|null $authorId
	 */
	public function onItemsUnbind(ItemIdentifier $parent, ItemIdentifier $child, int $authorId = null): void
	{
		$this->registerUnbindEvent($parent, $child, $authorId);
		$this->registerUnbindEvent($child, $parent, $authorId);
	}

	protected function registerBindEvent(
		ItemIdentifier $timelineOwner,
		ItemIdentifier $boundEntity,
		?int $authorId = null
	): void
	{
		$this->registerRelationEvent(
			TimelineEntry\Facade::LINK,
			$timelineOwner,
			$boundEntity,
			$authorId
		);
	}

	protected function registerUnbindEvent(
		ItemIdentifier $timelineOwner,
		ItemIdentifier $boundEntity,
		?int $authorId = null
	): void
	{
		$this->registerRelationEvent(
			TimelineEntry\Facade::UNLINK,
			$timelineOwner,
			$boundEntity,
			$authorId
		);
	}

	/**
	 * @param string $timelineEntryType - constant of TimelineEntry\Facade
	 * @param ItemIdentifier $timelineOwner - the resulting event will be displayed in this entity's timeline
	 * @param ItemIdentifier $boundEntity - the entity that was bound to the timeline owner
	 * @param int|null $authorId
	 */
	protected function registerRelationEvent(
		string $timelineEntryType,
		ItemIdentifier $timelineOwner,
		ItemIdentifier $boundEntity,
		?int $authorId = null
	): void
	{
		$timelineEntryId = Container::getInstance()->getTimelineEntryFacade()->create(
			$timelineEntryType,
			[
				'ENTITY_TYPE_ID' => $boundEntity->getEntityTypeId(),
				'ENTITY_ID' => $boundEntity->getEntityId(),
				'AUTHOR_ID' => ($authorId > 0) ? $authorId : static::getCurrentOrDefaultAuthorId(),
				'SETTINGS' => [],
				'BINDINGS' => [
					[
						'ENTITY_TYPE_ID' => $timelineOwner->getEntityTypeId(),
						'ENTITY_ID' => $timelineOwner->getEntityId()
					],
				],
			]
		);

		if ($timelineEntryId <= 0)
		{
			return;
		}

		$this->sendPullEventOnAdd($timelineOwner, $timelineEntryId);
	}
}
