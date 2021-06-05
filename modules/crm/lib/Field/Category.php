<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;
use Bitrix\Main\Result;

class Category extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$result = parent::processLogic($item, $context);
		if (!$result->isSuccess() || !$item->isChanged($this->getName()))
		{
			return $result;
		}

		// todo check that assigned is filled ?

		// we do not check here whether categories enabled or not. If we are here - lets do our work.
		$newCategoryId = $item->get($this->getName());
		// we could move available categories into field settings
		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		$categories = $factory->getCategories();
		$categoryIds = [];
		foreach ($categories as $category)
		{
			$categoryIds[] = $category->getId();
		}
		if(!in_array($newCategoryId, $categoryIds, true))
		{
			return $result->addError($this->getValueNotValidError());
		}

		// todo check running automation

		return $result;
	}

	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$result = parent::processAfterSave($itemBeforeSave, $item, $context);
		if (!$result->isSuccess())
		{
			return $result;
		}
		if ($itemBeforeSave->get($this->getName()) === $item->get($this->getName()))
		{
			return $result;
		}

		$userPermissions = Container::getInstance()->getUserPermissions();
		$userPermissions->deleteItemAttributes($itemBeforeSave);

		// todo reset counters

		return $result;
	}
}