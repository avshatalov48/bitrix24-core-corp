<?php

namespace Bitrix\Crm;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;

class ItemIdentifier
{
	/** @var int */
	private $entityTypeId;
	/** @var int */
	private $entityId;

	/**
	 * ItemIdentifier constructor.
	 *
	 * @param int $entityTypeId
	 * @param int $entityId
	 */
	public function __construct(int $entityTypeId, int $entityId)
	{
		$this->setEntityTypeId($entityTypeId);
		$this->setEntityId($entityId);
	}

	/**
	 * Creates a new ItemIdentifier object, that is based on the provided $item
	 *
	 * @param Item $item
	 *
	 * @return ItemIdentifier
	 */
	public static function createByItem(Item $item): ItemIdentifier
	{
		return new static($item->getEntityTypeId(), $item->getId());
	}

	/**
	 * Returns $entityTypeId of the item
	 *
	 * @return int
	 */
	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	private function setEntityTypeId(int $entityTypeId): ItemIdentifier
	{
		if (!\CCrmOwnerType::IsDefined($entityTypeId))
		{
			throw new ArgumentException('The provided $entityTypeId is invalid', 'entityTypeId');
		}

		$this->entityTypeId = $entityTypeId;

		return $this;
	}

	/**
	 * Returns $entityId of the item
	 *
	 * @return int
	 */
	public function getEntityId(): int
	{
		return $this->entityId;
	}

	private function setEntityId(int $entityId): ItemIdentifier
	{
		if ($entityId <= 0)
		{
			throw new ArgumentOutOfRangeException('The provided $entityId is invalid', 1);
		}

		$this->entityId = $entityId;

		return $this;
	}

	/**
	 * Transform this object to string
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		$entityTypeId = $this->getEntityTypeId();
		$entityName = \CCrmOwnerType::ResolveName($entityTypeId);
		$entityId = $this->getEntityId();

		return "Entity type ID: {$entityTypeId} ({$entityName}), entity ID: {$entityId}";
	}

	public function getHash(): string
	{
		return 'type_' . $this->getEntityTypeId() . '_id_' . $this->getEntityId();
	}
}
