<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class IsReturnCustomer extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$value = $item->get($this->getName());
		if ($item->getCompanyId() > 0)
		{
			$value = true;
		}
		elseif ($item->getContactId() > 0 || !empty($item->getContactBindings()))
		{
			$value = true;
		}

		$item->set($this->getName(), $value);

		return new Result();
	}
}