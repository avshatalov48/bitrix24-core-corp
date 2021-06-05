<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

class UpdatedTime extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$item->set($this->name, new DateTime());

		return parent::processLogic($item, $context);
	}
}