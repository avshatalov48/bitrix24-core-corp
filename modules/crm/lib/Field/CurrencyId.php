<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Currency;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class CurrencyId extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$result = parent::processLogic($item, $context);

		if (!in_array($item->get($this->getName()), Currency::getCurrencyIds(), true))
		{
			$result->addError($this->getValueNotValidError());
		}

		return $result;
	}
}
