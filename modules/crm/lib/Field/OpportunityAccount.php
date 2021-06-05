<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Currency\Conversion;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class OpportunityAccount extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if (!is_null($item->getOpportunity()))
		{
			$opportunityAccount = Conversion::toAccountCurrency($item->getOpportunity(), $item->getCurrencyId());
			$item->setOpportunityAccount($opportunityAccount);
		}

		return parent::processLogic($item, $context);
	}
}