<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class ExchRate extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if (!$item->hasField(Item::FIELD_NAME_CURRENCY_ID))
		{
			return (new Result())->addError(new Error('Item has no CURRENCY_ID field'));
		}

		if (
			$item->isNew()
			|| $item->isChanged(Item::FIELD_NAME_CURRENCY_ID)
			|| (
				$item->hasField(Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY)
				&& $item->getIsManualOpportunity()
				&& $item->hasField(Item::FIELD_NAME_OPPORTUNITY)
				&& $item->isChanged(Item::FIELD_NAME_OPPORTUNITY)
			)
		)
		{
			$item->set(
				$this->getName(),
				\Bitrix\Crm\Currency\Conversion::getConversionRateToBaseCurrency($item->getCurrencyId()),
			);
		}

		return new Result();
	}
}
