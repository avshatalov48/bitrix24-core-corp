<?php

namespace Bitrix\Crm\Service\Operation\Action\Compatible\SendEvent;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action\Compatible;
use Bitrix\Main\Result;

class ProductRowsSave extends Compatible\SendEvent
{
	protected function executeEvent(array $event, Item $item): Result
	{
		$itemBeforeSave = $this->getItemBeforeSave();

		if (
			$item->hasField(Item::FIELD_NAME_PRODUCTS)
			&& $itemBeforeSave
			&& $itemBeforeSave->isChanged(Item::FIELD_NAME_PRODUCTS)
		)
		{
			$productsArray = $item->getProductRows() ? $item->getProductRows()->toArray() : [];

			ExecuteModuleEventEx($event, [$item->getId(), $productsArray]);
		}

		return new Result();
	}
}
