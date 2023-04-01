<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\ItemIdentifier;

/**
 * @deprecated
 */
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
	}

	public function onItemsBind(ItemIdentifier $parent, ItemIdentifier $child, ?int $authorId = null): void
	{
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
	}

	protected function registerBindEvent(
		ItemIdentifier $timelineOwner,
		ItemIdentifier $boundEntity,
		?int $authorId = null
	): void
	{
	}

	protected function registerUnbindEvent(
		ItemIdentifier $timelineOwner,
		ItemIdentifier $boundEntity,
		?int $authorId = null
	): void
	{
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
	}
}
