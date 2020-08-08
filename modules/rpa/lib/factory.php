<?php

namespace Bitrix\Rpa;

use Bitrix\Main\ORM\Entity;
use Bitrix\Main\UserField\Internal\TypeFactory;
use Bitrix\Rpa\Model\PrototypeItem;
use Bitrix\Rpa\Model\Item;
use Bitrix\Rpa\Model\PrototypeItemIndex;
use Bitrix\Rpa\Model\TypeTable;

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
		return 'rpa';
	}

	public function getItemParentClass(): string
	{
		return Item::class;
	}

	public function getAddCommand(Item $item): Command
	{
		return new Command\Add($item);
	}

	public function getUpdateCommand(Item $item): Command
	{
		return new Command\Update($item);
	}

	public function getDeleteCommand(Item $item): Command
	{
		return new Command\Delete($item);
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
}