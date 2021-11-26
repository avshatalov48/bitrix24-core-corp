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
		$name = $item->get(Item::FIELD_NAME_NAME);
		$lastName = $item->get(Item::FIELD_NAME_LAST_NAME);
		$secondName = $item->get(Item::FIELD_NAME_SECOND_NAME);

		$fullName = '';

		if (!empty($name) || !empty($lastName))
		{
			if (!empty($name))
			{
				$fullName .= $name . ' ';
			}
			if (!empty($lastName))
			{
				$fullName .= $lastName . ' ';
			}
			if (!empty($secondName))
			{
				$fullName .= $secondName;
			}
		}

		$item->set($this->getName(), trim($fullName));

		return new Result;
	}
}