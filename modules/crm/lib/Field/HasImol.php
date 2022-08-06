<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class HasImol extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if ($item->hasField(Item::FIELD_NAME_FM))
		{
			$hasImol = \CCrmFieldMulti::HasImolValues($item->getFm()->toArray());

			$item->set($this->getName(), $hasImol);
		}

		return new Result();
	}
}
