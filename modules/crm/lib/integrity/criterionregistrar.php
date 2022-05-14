<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Crm\Integrity\CriterionRegistrar\Data;
use Bitrix\Crm\Item;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

abstract class CriterionRegistrar
{
	abstract public function register(Data $data): Result;

	/**
	 * Alias for @see CriterionRegistrar::register()
	 * Extracts necessary data from Item and calls 'register'
	 *
	 * @param Item $item
	 *
	 * @return Result
	 */
	final public function registerByItem(Item $item): Result
	{
		$data =
			(new Data())
				->setEntityTypeId($item->getEntityTypeId())
				->setEntityId($item->getId())
				->setCurrentFields($item->getCompatibleData())
		;

		return $this->register($data);
	}

	abstract public function update(Data $data): Result;

	/**
	 * Alias for @see CriterionRegistrar::update()
	 * Extracts necessary data from Items and calls 'update'
	 *
	 * @param Item $itemBeforeSave
	 * @param Item $item
	 *
	 * @return Result
	 */
	final public function updateByItem(Item $itemBeforeSave, Item $item): Result
	{
		$data =
			(new Data())
				->setEntityTypeId($item->getEntityTypeId())
				->setEntityId($item->getId())
				->setPreviousFields($itemBeforeSave->getCompatibleData(Values::ACTUAL))
				->setCurrentFields($item->getCompatibleData())
		;

		return $this->update($data);
	}

	abstract public function unregister(Data $data): Result;

	/**
	 * Alias for @see CriterionRegistrar::unregister()
	 * Extracts necessary data from Item and calls 'unregister'
	 *
	 * @param Item $itemBeforeDeletion
	 *
	 * @return Result
	 */
	final public function unregisterByItem(Item $itemBeforeDeletion): Result
	{
		$data =
			(new Data())
				->setEntityTypeId($itemBeforeDeletion->getEntityTypeId())
				->setEntityId($itemBeforeDeletion->getId())
		;

		return $this->unregister($data);
	}

	public function isNull(): bool
	{
		return false;
	}
}
