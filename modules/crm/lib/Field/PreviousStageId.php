<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class PreviousStageId extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if($item->isChanged(Item::FIELD_NAME_STAGE_ID))
		{
			$item->set($this->name, $item->remindActual(Item::FIELD_NAME_STAGE_ID));
		}

		return parent::processLogic($item, $context);
	}
}