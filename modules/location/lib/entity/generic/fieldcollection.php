<?php

namespace Bitrix\Location\Entity\Generic;

use Bitrix\Main\SystemException;

/**
 * Class FieldCollection
 * @package Bitrix\Location\Entity\Generic
 */
abstract class FieldCollection extends Collection
{
	/** @var IField[] */
	protected $items = [];

	/**
	 * @param int $type
	 * @return bool
	 */
	public function isItemExist(int $type)
	{
		foreach($this->items as $item)
		{
			if($item->getType() === $type)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param int $type
	 * @return IField|null
	 */
	public function getItemByType(int $type): ?IField
	{
		foreach($this->items as $item)
		{
			if($item->getType() === $type)
			{
				return $item;
			}
		}

		return null;
	}

	/**
	 * @param mixed $item
	 * @return int
	 * @throws SystemException
	 */
	public function addItem($item)
	{
		if($this->isItemExist($item->getType()))
		{
			throw new SystemException('Item with type "'.$item->getType().'" already exist in this collection');
		}

		return parent::addItem($item);
	}

}
