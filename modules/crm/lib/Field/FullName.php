<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class FullName extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$name = $item->hasField(Item::FIELD_NAME_NAME) ? $item->getName() : null;
		$lastName = $item->hasField(Item::FIELD_NAME_LAST_NAME) ? $item->getLastName() : null;

		$fullName = '';

		if (!empty($name) && !empty($lastName))
		{
			$fullName = "{$name} {$lastName}";
		}
		elseif (!empty($lastName))
		{
			$fullName = $lastName;
		}
		elseif (!empty($name))
		{
			$fullName = $name;
		}

		$fullName = trim($fullName);

		$item->set($this->getName(), $fullName);

		return new Result();
	}
}
