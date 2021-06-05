<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

class CreatedTime extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if ($item->isNew())
		{
			$item->set($this->name, new DateTime());
		}
		else
		{
			$item->reset($this->name);
		}

		return parent::processLogic($item, $context);
	}
}