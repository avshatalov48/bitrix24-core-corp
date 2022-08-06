<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;

final class LastName extends Field
{
	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$result = new FieldAfterSaveResult();

		$isNameEmpty = $item->hasField(Item::FIELD_NAME_NAME) && empty($item->getName());
		$isLastNameEmpty = $this->isItemValueEmpty($item);

		$defaultLastName = $item->getTitlePlaceholder();
		if ($isNameEmpty && $isLastNameEmpty && !empty($defaultLastName))
		{
			$result->setNewValue($this->getName(), $defaultLastName);
		}

		return $result;
	}
}
