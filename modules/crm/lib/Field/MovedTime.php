<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

class MovedTime extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if($item->isChanged(Item::FIELD_NAME_STAGE_ID))
		{
			$item->set($this->name, new DateTime());
		}

		return parent::processLogic($item, $context);
	}
}