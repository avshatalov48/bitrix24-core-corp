<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Relation\StorageStrategy;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Result;

class Relation
{
	/** @var RelationIdentifier */
	protected $relationIdentifier;
	/** @var Relation\Settings */
	protected $settings;
	/** @var StorageStrategy */
	protected $storageStrategy;

	/**
	 * Create a new Relation Object
	 *
	 * @param int $parentEntityTypeId
	 * @param int $childEntityTypeId
	 * @param bool $isChildrenListEnabled
	 * @deprecated
	 *
	 * @return Relation
	 */
	public static function create(
		int $parentEntityTypeId,
		int $childEntityTypeId,
		bool $isChildrenListEnabled = true
	): Relation
	{
		$settings =
			(new Relation\Settings())
				->setIsChildrenListEnabled($isChildrenListEnabled)
				->setIsPredefined(false)
		;

		return new static(
			new RelationIdentifier($parentEntityTypeId, $childEntityTypeId),
			$settings
		);
	}

	/**
	 * Create a new Relation Object, that represents a predefined (set on the system level) relation
	 *
	 * @internal Client code should not create predefined relations since they all set on the system level
	 * @deprecated
	 *
	 * @param int $parentEntityTypeId
	 * @param int $childEntityTypeId
	 *
	 * @return Relation
	 */
	public static function createPredefined(int $parentEntityTypeId, int $childEntityTypeId): Relation
	{
		$settings =
			(new Relation\Settings())
				->setIsChildrenListEnabled(true)
				->setIsPredefined(true)
		;

		return new static(
			new RelationIdentifier($parentEntityTypeId, $childEntityTypeId),
			$settings
		);
	}

	public function __construct(
		RelationIdentifier $identifier,
		Relation\Settings $settings
	)
	{
		$this->relationIdentifier = $identifier;
		$this->settings = $settings;
	}

	/**
	 * Returns true if this relation is described by the identifier (has same parent and child entityTypeId)
	 *
	 * @param RelationIdentifier $identifier
	 *
	 * @return bool
	 */
	public function hasEqualIdentifier(RelationIdentifier $identifier): bool
	{
		// not strict comparison to deem two different instances with same values as equal
		return ($this->relationIdentifier == $identifier);
	}

	/**
	 * Returns an identifier that is associated with this relation
	 *
	 * @return RelationIdentifier
	 */
	public function getIdentifier(): RelationIdentifier
	{
		return $this->relationIdentifier;
	}

	/**
	 * Returns $entityTypeId of the parent type
	 *
	 * @return int
	 */
	public function getParentEntityTypeId(): int
	{
		return $this->relationIdentifier->getParentEntityTypeId();
	}

	/**
	 * Returns $entityTypeId of the child type
	 *
	 * @return int
	 */
	public function getChildEntityTypeId(): int
	{
		return $this->relationIdentifier->getChildEntityTypeId();
	}

	/**
	 * Returns true if this relation is predefined (set on the system level) and can not be changed, unbound, etc.
	 *
	 * @return bool
	 */
	public function isPredefined(): bool
	{
		return $this->settings->isPredefined();
	}

	/**
	 * Returns true if a list (grid) of child elements, associated with this relation,
	 * should be displayed on a parent element details page
	 * Always true for predefined relations
	 *
	 * @return bool
	 */
	public function isChildrenListEnabled(): bool
	{
		return $this->settings->isChildrenListEnabled();
	}

	/**
	 * @param bool $isChildrenListEnabled
	 * @return $this
	 */
	public function setChildrenListEnabled(bool $isChildrenListEnabled): self
	{
		$this->settings->setIsChildrenListEnabled($isChildrenListEnabled);

		return $this;
	}

	/**
	 * Set a StorageStrategy object, that would be used in methods that communicate with the DB.
	 * (Relation::areItemsBound, Relation::bindItems, etc.)
	 *
	 * @param StorageStrategy $storageStrategy
	 *
	 * @return $this
	 */
	public function setStorageStrategy(StorageStrategy $storageStrategy): Relation
	{
		$this->storageStrategy = $storageStrategy;

		return $this;
	}

	protected function getStorageStrategy(): StorageStrategy
	{
		if (!$this->storageStrategy)
		{
			throw new ObjectNotFoundException('Storage strategy is not set');
		}

		return $this->storageStrategy;
	}

	/**
	 * Returns true if the items are bound
	 *
	 * @param ItemIdentifier $parent
	 * @param ItemIdentifier $child
	 *
	 * @return bool
	 */
	public function areItemsBound(ItemIdentifier $parent, ItemIdentifier $child): bool
	{
		$this->validateParent($parent);
		$this->validateChild($child);

		return $this->getStorageStrategy()->areItemsBound($parent, $child);
	}

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
		$this->validateParent($parent);
		$this->validateChild($child);

		return $this->getStorageStrategy()->bindItems($parent, $child);
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
		$this->validateParent($parent);
		$this->validateChild($child);

		return $this->getStorageStrategy()->unbindItems($parent, $child);
	}

	/**
	 * Unbind the provided items
	 *
	 * @param ItemIdentifier $oldItem
	 * @param ItemIdentifier $newItem
	 *
	 * @return Result
	 */
	public function replaceAllItemBindings(ItemIdentifier $oldItem, ItemIdentifier $newItem): Result
	{
		$this->validateParent($oldItem);
		$this->validateParent($newItem);

		return $this->getStorageStrategy()->replaceAllItemBindings($oldItem, $newItem);
	}

	/**
	 * Returns ItemIdentifier objects of elements that are parents to the provided child
	 * Only the items that are bound by this relation are taken into account
	 *
	 * @param ItemIdentifier $child
	 *
	 * @return ItemIdentifier[]
	 */
	public function getParentElements(ItemIdentifier $child): array
	{
		$this->validateChild($child);

		return $this->getStorageStrategy()->getParentElements($child, $this->getParentEntityTypeId());
	}

	/**
	 * Returns ItemIdentifier objects of elements that are children to the provided parent
	 * Only the items that are bound by this relation are taken into account
	 *
	 * @param ItemIdentifier $parent
	 *
	 * @return ItemIdentifier[]
	 */
	public function getChildElements(ItemIdentifier $parent): array
	{
		$this->validateParent($parent);

		return $this->getStorageStrategy()->getChildElements($parent, $this->getChildEntityTypeId());
	}

	protected function validateParent(ItemIdentifier $parent): void
	{
		if ($this->getParentEntityTypeId() !== $parent->getEntityTypeId())
		{
			throw new ArgumentException(
				'The provided parent has entityTypeId that does not match parent entityTypeId in the relation object',
				'parent'
			);
		}
	}

	protected function validateChild(ItemIdentifier $child): void
	{
		if ($this->getChildEntityTypeId() !== $child->getEntityTypeId())
		{
			throw new ArgumentException(
				'The provided child has entityTypeId that does not match child entityTypeId in the relation object',
				'child'
			);
		}
	}

	public function setSettings(Relation\Settings $settings): self
	{
		$this->settings = $settings;

		return $this;
	}

	public function getSettings(): Relation\Settings
	{
		return $this->settings;
	}
}
