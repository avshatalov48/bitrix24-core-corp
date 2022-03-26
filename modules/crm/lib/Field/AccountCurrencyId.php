<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Currency;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class AccountCurrencyId extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$item->set($this->getName(), Currency::getAccountCurrencyId());

		return parent::processLogic($item, $context);
	}
}
