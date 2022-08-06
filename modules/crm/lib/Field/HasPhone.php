<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class HasPhone extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if ($item->hasField(Item::FIELD_NAME_FM))
		{
			$hasEmail = \CCrmFieldMulti::HasValues($item->getFm()->toArray(), \CCrmFieldMulti::PHONE);

			$item->set($this->getName(), $hasEmail);
		}

		return new Result();
	}
}
