<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class CreatedBy extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if(!$context)
		{
			$context = Container::getInstance()->getContext();
		}
		if ($item->isNew())
		{
			$item->set($this->name, $context->getUserId());
		}
		else
		{
			$item->reset($this->name);
		}

		return parent::processLogic($item, $context);
	}
}