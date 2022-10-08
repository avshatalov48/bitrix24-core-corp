<?php

namespace Bitrix\Crm\Model\Dynamic;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory\SmartDocument;
use Bitrix\Crm\Service\Factory\SmartInvoice;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\UserField\Internal\TypeFactory;

class Factory extends TypeFactory
{
	protected $itemIndexEntities = [];

	/**
	 * @return TypeTable
	 */
	public function getTypeDataClass(): string
	{
		return TypeTable::class;
	}

	public function getItemPrototypeDataClass(): string
	{
		return PrototypeItem::class;
	}

	public function getCode(): string
	{
		return 'crm';
	}

	public function getItemParentClass(): string
	{
		return Item::class;
	}

	public function getItemIndexPrototypeDataClass(): string
	{
		return PrototypeItemIndex::class;
	}

	/**
	 * @param $type
	 * @return PrototypeItemIndex
	 */
	public function getItemIndexDataClass($type): string
	{
		return $this->getItemIndexEntity($type)->getDataClass();
	}

	public function getItemIndexEntity($type): Entity
	{
		$typeData = $this->getTypeDataClass()::resolveType($type);
		if(!empty($typeData) && isset($this->itemIndexEntities[$typeData['ID']]))
		{
			return $this->itemIndexEntities[$typeData['ID']];
		}

		$entity = $this->getTypeDataClass()::compileItemIndexEntity($type);
		if($entity)
		{
			$this->itemIndexEntities[$typeData['ID']] = $entity;
		}

		return $entity;
	}

	public function getUserFieldSuspendedEntityId(int $typeId): string
	{
		return $this->getUserFieldEntityPrefix().$typeId.'_SPD';
	}

	public function getUserFieldEntityId(int $typeId): string
	{
		$type = Container::getInstance()->getType($typeId);
		if ($type && $type->getEntityTypeId() === \CCrmOwnerType::SmartInvoice)
		{
			return SmartInvoice::USER_FIELD_ENTITY_ID;
		}
		if ($type && $type->getEntityTypeId() === \CCrmOwnerType::SmartDocument)
		{
			return SmartDocument::USER_FIELD_ENTITY_ID;
		}

		return parent::getUserFieldEntityId($typeId);
	}

	public function prepareIdentifier($identifier)
	{
		if ($identifier === 'SMART_INVOICE')
		{
			$type = Container::getInstance()->getTypeByEntityTypeId(\CCrmOwnerType::SmartInvoice);
			if ($type)
			{
				return $type->getId();
			}
		}
		if ($identifier === 'SMART_DOCUMENT')
		{
			$type = Container::getInstance()->getTypeByEntityTypeId(\CCrmOwnerType::SmartDocument);
			if ($type)
			{
				return $type->getId();
			}
		}

		return parent::prepareIdentifier($identifier);
	}
}
