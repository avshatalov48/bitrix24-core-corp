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

		$defaultTitle = $item->getTitlePlaceholder();
		if (!empty($defaultTitle) && $this->isItemValueEmpty($item))
		{
			$result->setNewValue($this->getName(), $defaultTitle);
		}

		return $result;
	}
}
