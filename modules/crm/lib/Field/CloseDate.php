<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;

class CloseDate extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if(
			$item->hasField(Item::FIELD_NAME_CLOSED)
			&& $item->get(Item::FIELD_NAME_CLOSED) === true
			&& $this->isItemValueEmpty($item)
		)
		{
			$item->set($this->getName(), new Date());
		}

		return parent::processLogic($item, $context);
	}
}