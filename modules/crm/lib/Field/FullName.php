<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;

class FullName extends Field
{
	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
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

		$result = new FieldAfterSaveResult();

		return $result->setNewValue($this->getName(), trim($fullName));
	}
}
