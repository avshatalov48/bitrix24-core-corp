<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;

class Title extends Field
{
	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$result = new FieldAfterSaveResult();

		if (empty($item->get($this->getName())) && !empty($item->getTitlePlaceholder()))
		{
			$result->setNewValue($this->getName(), $item->getTitlePlaceholder());
		}

		return $result;
	}
}
