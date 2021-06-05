<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Currency\Conversion;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class TaxValueAccount extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if (!is_null($item->getTaxValue()))
		{
			$taxValueAccount = Conversion::toAccountCurrency($item->getTaxValue(), $item->getCurrencyId());
			$item->setTaxValueAccount($taxValueAccount);
		}

		return parent::processLogic($item, $context);
	}
}