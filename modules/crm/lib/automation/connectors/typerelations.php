<?php

namespace Bitrix\Crm\Automation\Connectors;

use Bitrix\Crm\Item;
use Bitrix\Crm\Relation\Collection;
use Bitrix\Crm\Relation\RelationManager;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\ParentFieldManager;

class TypeRelations
{
	private RelationManager $relationManager;
	private int $entityTypeId;

	public function __construct(Factory $factory)
	{
		$this->relationManager = Container::getInstance()->getRelationManager();
		$this->entityTypeId = $factory->getEntityTypeId();
	}

	/**
	 * Creates object instance from Item
	 *
	 * @param Item $item
	 * @return static
	 */
	public static function createFromItem(Item $item): static
	{
		return new static(Container::getInstance()->getFactory($item->getEntityTypeId()));
	}

	/**
	 * Returns true if types are bound as parent child
	 *
	 * @param int $parentTypeId
	 * @return bool
	 */
	public function isParentType(int $parentTypeId): bool
	{
		if ($parentTypeId === $this->entityTypeId)
		{
			return false;
		}

		/**
		 * Constructor throws exception if parent type is the same as child type, but
		 * this is checked above
		 * @noinspection PhpUnhandledExceptionInspection
		 */
		$relation = new RelationIdentifier($parentTypeId, $this->entityTypeId);

		return $this->relationManager->areTypesBound($relation);
	}

	/**
	 * Returns parent relations type ids of item
	 *
	 * @return int[]
	 */
	public function getParentTypeIds(): array
	{
		$parents = [];

		foreach ($this->getParentRelations() as $relation)
		{
			$parents[] = $relation->getParentEntityTypeId();
		}

		return $parents;
	}

	private function getParentRelations(): Collection
	{
		return
			$this
				->relationManager
				->getParentRelations($this->entityTypeId)
				->filterOutPredefinedRelations()
		;
	}

	public function getParentFieldName(int $parentTypeId): string
	{
		return ParentFieldManager::getParentFieldName($parentTypeId);
	}

	public function isParentFieldName(string $fieldName): bool
	{
		return ParentFieldManager::isParentFieldName($fieldName);
	}
}