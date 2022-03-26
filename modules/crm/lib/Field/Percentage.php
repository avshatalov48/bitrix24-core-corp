<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

/**
 * General class for value in percents
 */
class Percentage extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$value = $item->get($this->getName());

		if ($value > 100)
		{
			$item->set($this->getName(), 100);
		}
		elseif ($value < 0)
		{
			$item->set($this->getName(), 0);
		}

		return new Result();
	}
}
