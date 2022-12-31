<?php

namespace Bitrix\Crm\Integration\Catalog\Contractor;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Relation\StorageStrategy;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

/**
 * Class DocumentRelationStorageStrategy
 *
 * @package Bitrix\Crm\Integration\Catalog\Contractor
 */
class DocumentRelationStorageStrategy extends StorageStrategy
{
	/** @var int */
	private int $entityTypeId;

	/**
	 * BaseStrategy constructor.
	 *
	 * @param int $entityTypeId
	 */
	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
	}

	/**
	 * @inheritDoc
	 */
	public function getParentElements(ItemIdentifier $child, int $parentEntityTypeId): array
	{
		$parents = [];

		if (
			$child->getEntityTypeId() === \CCrmOwnerType::StoreDocument
			&& $parentEntityTypeId === $this->entityTypeId
		)
		{
			$items = StoreDocumentContractorTable::query()
				->setSelect(['ENTITY_ID'])
				->where('DOCUMENT_ID', $child->getEntityId())
				->where('ENTITY_TYPE_ID', $this->entityTypeId)
				->exec();

			while ($item = $items->fetch())
			{
				$parents[] = new ItemIdentifier(
					$this->entityTypeId,
					(int)$item['ENTITY_ID']
				);
			}
		}

		return $parents;
	}

	/**
	 * @inheritDoc
	 */
	public function getChildElements(ItemIdentifier $parent, int $childEntityTypeId): array
	{
		$children = [];

		if (
			$parent->getEntityTypeId() === $this->entityTypeId
			&& $childEntityTypeId === \CCrmOwnerType::StoreDocument
		)
		{
			$items = StoreDocumentContractorTable::query()
				->setSelect(['DOCUMENT_ID'])
				->where('ENTITY_ID', $parent->getEntityId())
				->where('ENTITY_TYPE_ID', $this->entityTypeId)
				->exec();

			while ($item = $items->fetch())
			{
				$children[] = new ItemIdentifier(
					$this->entityTypeId,
					(int)$item['DOCUMENT_ID']
				);
			}
		}

		return $children;
	}

	/**
	 * @inheritDoc
	 */
	public function areItemsBound(ItemIdentifier $parent, ItemIdentifier $child): bool
	{
		if ($parent->getEntityTypeId() !== \CCrmOwnerType::StoreDocument)
		{
			return false;
		}

		return (new ContactCompanyBinding($child->getEntityTypeId()))->isDocumentBoundToEntity(
			$parent->getEntityId(),
			$child->getEntityId()
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function createBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		return (new Result())->addError(new Error('Not supported'));
	}

	/**
	 * @inheritDoc
	 */
	protected function deleteBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		return (new Result())->addError(new Error('Not supported'));
	}

	/**
	 * @inheritDoc
	 */
	protected function replaceBindings(ItemIdentifier $fromItem, ItemIdentifier $toItem): Result
	{
		(new ContactCompanyBinding($this->entityTypeId))->rebind(
			$fromItem->getEntityId(),
			$toItem->getEntityId()
		);

		return new Result();
	}
}
