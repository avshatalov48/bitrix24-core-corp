<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\BirthdayReminder;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class BirthdaySort extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if ($item->hasField(Item::FIELD_NAME_BIRTHDATE))
		{
			$birthDate = $item->get(Item::FIELD_NAME_BIRTHDATE);

			if (empty($birthDate))
			{
				$birthDate = '';
			}

			$item->set($this->getName(), BirthdayReminder::prepareSorting($birthDate));
		}

		return new Result();
	}
}