<?php

namespace Bitrix\Crm\Model\Dynamic;

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
}