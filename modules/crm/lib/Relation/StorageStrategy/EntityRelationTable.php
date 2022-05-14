<?php

namespace Bitrix\Crm\Relation\StorageStrategy;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Relation;
use Bitrix\Crm\Relation\RelationType;
use Bitrix\Crm\Relation\StorageStrategy;
use Bitrix\Main\Result;

class EntityRelationTable extends StorageStrategy
{
	protected const RELATION_TYPE = RelationType::BINDING;

	/** @var Relation\EntityRelationTable */
	protected static $tableClass = Relation\EntityRelationTable::class;

	/**
	 * @inheritDoc
	 */
	public function getParentElements(ItemIdentifier $child, int $parentEntityTypeId): array
	{
		$collection =
			static::$tableClass::getList([
				'filter' => [
					'=DST_ENTITY_TYPE_ID' => $child->getEntityTypeId(),
					'=DST_ENTITY_ID' => $child->getEntityId(),
					'=SRC_ENTITY_TYPE_ID' => $parentEntityTypeId,
				],
			])
				->fetchCollection()
		;

		$parents = [];
		foreach ($collection as $entityObject)
		{
			$parents[] = new ItemIdentifier($entityObject->getSrcEntityTypeId(), $entityObject->getSrcEntityId());
		}

		return $parents;
	}

	/**
	 * @inheritDoc
	 */
	public function getChildElements(ItemIdentifier $parent, int $childEntityTypeId): array
	{
		$collection =
			static::$tableClass::getList([
				'filter' => [
					'=SRC_ENTITY_TYPE_ID' => $parent->getEntityTypeId(),
					'=SRC_ENTITY_ID' => $parent->getEntityId(),
					'=DST_ENTITY_TYPE_ID' => $childEntityTypeId,
				],
			])
				->fetchCollection()
		;

		$children = [];
		foreach ($collection as $entityObject)
		{
			$children[] = new ItemIdentifier($entityObject->getDstEntityTypeId(), $entityObject->getDstEntityId());
		}

		return $children;
	}

	/**
	 * @inheritDoc
	 */
	public function areItemsBound(ItemIdentifier $parent, ItemIdentifier $child): bool
	{
		return (static::$tableClass::getCount([
			'=SRC_ENTITY_TYPE_ID' => $parent->getEntityTypeId(),
			'=SRC_ENTITY_ID' => $parent->getEntityId(),
			'=DST_ENTITY_TYPE_ID' => $child->getEntityTypeId(),
			'=DST_ENTITY_ID' => $child->getEntityId(),
		]) > 0);
	}

	/**
	 * @inheritDoc
	 */
	protected function createBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		$entityObject = static::$tableClass::createObject();
		$entityObject
			->setSrcEntityTypeId($parent->getEntityTypeId())
			->setSrcEntityId($parent->getEntityId())
			->setDstEntityTypeId($child->getEntityTypeId())
			->setDstEntityId($child->getEntityId())
			->setRelationType(static::RELATION_TYPE)
		;

		return $entityObject->save();
	}

	/**
	 * @inheritDoc
	 */
	protected function deleteBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		return static::$tableClass::delete([
			'SRC_ENTITY_TYPE_ID' => $parent->getEntityTypeId(),
			'SRC_ENTITY_ID' => $parent->getEntityId(),
			'DST_ENTITY_TYPE_ID' => $child->getEntityTypeId(),
			'DST_ENTITY_ID' => $child->getEntityId(),
		]);
	}

	protected function replaceBindings(ItemIdentifier $fromItem, ItemIdentifier $toItem): Result
	{
		static::$tableClass::replaceBindings($fromItem, $toItem);

		return new Result();
	}
}
