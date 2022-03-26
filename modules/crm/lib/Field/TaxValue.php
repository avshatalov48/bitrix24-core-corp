<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Currency;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;
use Bitrix\Main\Error;

class TaxValue extends Field
{
	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$result = new FieldAfterSaveResult();

		$products = $itemBeforeSave->getProductRows();
		$productsAreNotFetched = is_null($products) && !$itemBeforeSave->isNew();
		if ($productsAreNotFetched)
		{
			if ($itemBeforeSave->isChanged($this->getName()))
			{
				$result->addError(new Error(
					"Products are not fetched. Can't sync tax value",
					static::ERROR_CODE_PRODUCTS_NOT_FETCHED
				));
			}

			return $result;
		}

		$taxValue = Container::getInstance()->getAccounting()->calculateByItem($item)->getTaxValue();

		$result->setNewValue($this->getName(), $taxValue);
		if ($item->hasField(Item::FIELD_NAME_TAX_VALUE_ACCOUNT))
		{
			$taxValueAccount = Currency\Conversion::toAccountCurrency($taxValue, $item->getCurrencyId());
			$result->setNewValue(Item::FIELD_NAME_TAX_VALUE_ACCOUNT, $taxValueAccount);
		}

		return $result;
	}
}
