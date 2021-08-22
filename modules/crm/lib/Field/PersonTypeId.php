<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class PersonTypeId extends Field
{
	/**
	 * @param Item\Quote $item
	 * @param Context|null $context
	 * @return Result
	 */
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$item->setPersonTypeId(Container::getInstance()->getAccounting()->resolvePersonTypeId($item));

		return parent::processLogic($item, $context);
	}
}