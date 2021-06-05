<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Accounting;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class Opportunity extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$parentResult = parent::processLogic($item, $context);

		$isManualOpportunity = $item->hasField(Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY) && $item->getIsManualOpportunity();
		$isAutoOpportunity = !$isManualOpportunity;

		$products = $item->getProductRows();
		$productsAreNotFetched = is_null($products) && !$item->isNew();
		if ($productsAreNotFetched)
		{
			if (!$item->isChanged($this->getName()))
			{
				return $parentResult;
			}
			if ($isManualOpportunity && $item->isChanged($this->getName()))
			{
				return $parentResult;
			}

			return $parentResult->addError(new Error(
				"Products are not fetched. Can't sync opportunity",
				static::ERROR_CODE_PRODUCTS_NOT_FETCHED
			));
		}

		if ($item->isNew())
		{
			$areProductsEmpty = is_null($products) || (count($products) <= 0);
			$productsWereDeleted = false;
			$itemAlwaysHadNoProducts = $areProductsEmpty;
		}
		else
		{
			$areProductsEmpty = (count($products) <= 0);
			$productsWereDeleted = $areProductsEmpty && $item->isChanged(Item::FIELD_NAME_PRODUCTS);
			$itemAlwaysHadNoProducts = $areProductsEmpty && !$item->isChanged(Item::FIELD_NAME_PRODUCTS);
		}

		if ($isManualOpportunity || $itemAlwaysHadNoProducts)
		{
			return $parentResult;
		}

		if ($isAutoOpportunity && !$areProductsEmpty)
		{
			$item->set($this->getName(), Accounting\Products::calculateSum($item, $products->getAll()));
		}
		elseif ($isAutoOpportunity && $productsWereDeleted)
		{
			$item->set($this->getName(), $item->getDefaultValue($this->getName()));
		}

		return $parentResult;
	}
}
