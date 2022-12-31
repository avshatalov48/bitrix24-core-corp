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

	/** @var int|null */
	private ?int $categoryId = null;

	/**
	 * ItemIdentifier constructor.
	 *
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @param int|null $categoryId
	 */
	public function __construct(int $entityTypeId, int $entityId, ?int $categoryId = null)
	{
		$this->setEntityTypeId($entityTypeId);
		$this->setEntityId($entityId);
		$this->setCategoryId($categoryId);
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
		if (!\CCrmOwnerType::isCorrectEntityTypeId($entityTypeId))
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
	 * @param int|null $categoryId
	 * @return ItemIdentifier
	 */
	private function setCategoryId(?int $categoryId): ItemIdentifier
	{
		$this->categoryId = $categoryId;

		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getCategoryId(): ?int
	{
		return $this->categoryId;
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

	/**
	 * Return array representation of this object
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'ENTITY_TYPE_ID' => $this->getEntityTypeId(),
			'ENTITY_ID' => $this->getEntityId(),
			'CATEGORY_ID' => $this->getCategoryId(),
		];
	}

	public static function createFromArray(array $data): ?self
	{
		if (isset($data['ENTITY_TYPE_ID']) && isset($data['ENTITY_ID']))
		{
			return new self((int)$data['ENTITY_TYPE_ID'], (int)$data['ENTITY_ID']);
		}

		return null;
	}
}
