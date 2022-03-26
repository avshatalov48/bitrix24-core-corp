<?php

namespace Bitrix\Crm;

use Bitrix\Main\ArgumentException;

class RelationIdentifier
{
	private $parentEntityTypeId;
	private $childEntityTypeId;

	/**
	 * RelationIdentifier constructor.
	 *
	 * @param int $parentEntityTypeId
	 * @param int $childEntityTypeId
	 *
	 * @throws ArgumentException
	 */
	public function __construct(int $parentEntityTypeId, int $childEntityTypeId)
	{
		if ($parentEntityTypeId === $childEntityTypeId)
		{
			throw new ArgumentException(
				'An entity type can\'t be a parent/child type to itself ($parentEntityTypeId === $childEntityTypeId)'
			);
		}

		$this->setParentEntityTypeId($parentEntityTypeId);
		$this->setChildEntityTypeId($childEntityTypeId);
	}

	/**
	 * Returns $entityTypeId of the parent type
	 *
	 * @return int
	 */
	public function getParentEntityTypeId(): int
	{
		return $this->parentEntityTypeId;
	}

	/**
	 * Returns $entityTypeId of the child type
	 *
	 * @return int
	 */
	public function getChildEntityTypeId(): int
	{
		return $this->childEntityTypeId;
	}

	public function __toString(): string
	{
		$parentTypeName = \CCrmOwnerType::ResolveName($this->parentEntityTypeId);
		$childTypeName = \CCrmOwnerType::ResolveName($this->childEntityTypeId);

		return
			"Relation ID: Parent Type {$this->parentEntityTypeId} ({$parentTypeName}), "
			. "Child Type {$this->childEntityTypeId} ({$childTypeName})"
		;
	}

	public function getHash(): string
	{
		return "relation_id_parent_type_{$this->parentEntityTypeId}_child_type_{$this->childEntityTypeId}";
	}

	private function setParentEntityTypeId(int $parentEntityTypeId): RelationIdentifier
	{
		$this->validateEntityTypeId($parentEntityTypeId);

		$this->parentEntityTypeId = $parentEntityTypeId;

		return $this;
	}

	private function setChildEntityTypeId(int $childEntityTypeId): RelationIdentifier
	{
		$this->validateEntityTypeId($childEntityTypeId);

		$this->childEntityTypeId = $childEntityTypeId;

		return $this;
	}

	private function validateEntityTypeId(int $entityTypeId): void
	{
		if ($entityTypeId <= 0)
		{
			throw new ArgumentException('The provided $entityTypeId is invalid', 'entityTypeId');
		}
	}
}
