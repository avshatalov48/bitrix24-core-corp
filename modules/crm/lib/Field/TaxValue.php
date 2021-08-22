<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class TaxValue extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$parentResult = parent::processLogic($item, $context);

		$products = $item->getProductRows();
		$productsAreNotFetched = is_null($products) && !$item->isNew();
		if ($productsAreNotFetched)
		{
			if ($item->isChanged($this->getName()))
			{
				$parentResult->addError(new Error(
					"Products are not fetched. Can't sync tax value",
					static::ERROR_CODE_PRODUCTS_NOT_FETCHED
				));
			}

			return $parentResult;
		}

		if (!is_null($products) && count($products) > 0)
		{
			$item->set($this->getName(), Container::getInstance()->getAccounting()->calculateByItem($item)->getTaxValue());
		}

		$productsWereDeleted = !$item->isNew() && (count($products) <= 0) && $item->isChanged(Item::FIELD_NAME_PRODUCTS);
		if ($productsWereDeleted)
		{
			$item->set($this->getName(), $item->getDefaultValue($this->getName()));
		}

		return $parentResult;
	}
}
