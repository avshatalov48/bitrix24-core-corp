<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

final class Multifield extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$fm = $item->get($this->getName());
		$storage = Container::getInstance()->getMultifieldStorage();

		return $storage->validate($fm);
	}
}
