<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class IsMyCompany extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$result = new Result();

		if (!$item->isCategoriesSupported())
		{
			return $result;
		}

		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		if (!$factory)
		{
			return $result->addError($this->getFactoryNotFoundError($item->getEntityTypeId()));
		}

		$defaultCategory = $factory->createDefaultCategoryIfNotExist();

		if ($item->get($this->getName()) && $item->getCategoryId() !== $defaultCategory->getId())
		{
			return $result->addError(
				new Error(
					Loc::getMessage('CRM_ERROR_FIELD_MY_COMPANY_IN_CUSTOM_CATEGORY')
				)
			);
		}

		return $result;
	}
}
