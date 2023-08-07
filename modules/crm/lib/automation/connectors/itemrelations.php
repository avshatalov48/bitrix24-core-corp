<?php

namespace Bitrix\Crm\Automation\Connectors;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Relation\Registrar;
use Bitrix\Crm\Relation\RelationManager;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Result;

class ItemRelations
{
	private RelationManager $relationManager;
	private Registrar $registrar;
	private ItemIdentifier $item;

	public function __construct(ItemIdentifier $item)
	{
		$this->relationManager = Container::getInstance()->getRelationManager();
		$this->registrar = Container::getInstance()->getRelationRegistrar();

		$this->item = $item;
	}

	/**
	 * Returns object instance by Item
	 *
	 * @param Item $item
	 * @return static
	 *
	 * TODO - replace static with self when appropriate php version can be used
	 */
	public static function createFromItem(Item $item): self
	{
		$categoryId = $item->isCategoriesSupported() ? $item->getCategoryId() : null;
		$identifier = new ItemIdentifier($item->getEntityTypeId(), $item->getId(), $categoryId);

		return new static($identifier);
	}

	/**
	 * Returns parent elements to item
	 *
	 * @param int|null $parentTypeId
	 * @return array
	 */
	public function getParentElementIdentifiers(?int $parentTypeId = null): array
	{
		if ($parentTypeId === $this->item->getEntityTypeId())
		{
			return [];
		}

		if (isset($parentTypeId))
		{
			/**
			 * Constructor doesn't throw exceptions due to above check
			 *
			 * @noinspection PhpUnhandledExceptionInspection
			 */
			return
				$this
					->relationManager
					->getRelation(new RelationIdentifier($parentTypeId, $this->item->getEntityTypeId()))
					->getParentElements($this->item)
			;
		}
		else
		{
			return $this->relationManager->getParentElements($this->item);
		}
	}

	public function bindParentElement(ItemIdentifier $parent): Result
	{
		$bindResult = $this->relationManager->bindItems($parent, $this->item);
		if ($bindResult->isSuccess())
		{
			$historyResult = $this->registrar->registerBind($parent, $this->item);
			$bindResult->addErrors($historyResult->getErrors());
		}

		return $bindResult;
	}

	public function hasParentElement(): bool
	{
		return (bool)$this->relationManager->getParentElements($this->item);
	}
}