<?php

namespace Bitrix\Crm;

use Bitrix\Main\ArgumentException;
use Bitrix\Crm\Category\PermissionEntityTypeHelper;

class CategoryIdentifier implements \JsonSerializable
{
	/** @var int */
	private $entityTypeId;

	/** @var int|null */
	private ?int $categoryId = null;

	/**
	 * CategoryIdentifier constructor.
	 *
	 * @param int $entityTypeId
	 * @param int|null $categoryId
	 */
	public function __construct(int $entityTypeId, ?int $categoryId = null)
	{
		$this->setEntityTypeId($entityTypeId);
		$this->setCategoryId($categoryId);
	}

	/**
	 * Creates a new ItemIdentifier object, that is based on the provided $item
	 *
	 * @param Item $item
	 *
	 * @return CategoryIdentifier
	 */
	public static function createByItem(Item $item): self
	{
		$categoryId = $item->isCategoriesSupported() ? $item->getCategoryId() : null;

		return new static($item->getEntityTypeId(), $categoryId);
	}

	public static function createByParams(int $entityTypeId, ?int $categoryId = null): self|null
	{
		if (!\CCrmOwnerType::isCorrectEntityTypeId($entityTypeId))
		{
			return null;
		}

		return new static($entityTypeId, $categoryId);
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

	private function setEntityTypeId(int $entityTypeId): self
	{
		if (!\CCrmOwnerType::isCorrectEntityTypeId($entityTypeId))
		{
			throw new ArgumentException('The provided $entityTypeId is invalid', 'entityTypeId');
		}

		$this->entityTypeId = $entityTypeId;

		return $this;
	}

	/**
	 * @param int|null $categoryId
	 * @return CategoryIdentifier
	 */
	private function setCategoryId(?int $categoryId): self
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
		if (!is_null($this->getCategoryId()))
		{
			return "Entity type ID: {$entityTypeId} ({$entityName}), category ID: " . $this->getCategoryId();
		}

		return "Entity type ID: {$entityTypeId} ({$entityName})";
	}

	public function getHash(): string
	{
		return $this->getPermissionEntityCode();
	}

	public function getPermissionEntityCode(): string
	{
		return (new PermissionEntityTypeHelper($this->getEntityTypeId()))->getPermissionEntityTypeForCategory((int)$this->getCategoryId());
	}

	final public function jsonSerialize(): array
	{
		return [
			'entityTypeId' => $this->getEntityTypeId(),
			'categoryId' => $this->getCategoryId(),
		];
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
			'CATEGORY_ID' => $this->getCategoryId(),
		];
	}
}
