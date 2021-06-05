<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class MovedBy extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if(!$context)
		{
			$context = Container::getInstance()->getContext();
		}

		if($item->isChanged(Item::FIELD_NAME_STAGE_ID))
		{
			$item->set($this->name, $context->getUserId());
		}

		return parent::processLogic($item, $context);
	}
}