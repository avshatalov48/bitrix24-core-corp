<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Main\Result;

class SendProductRowsSaveEventCompatible extends SendEventCompatible
{
	protected function executeEvent(array $event, Item $item): Result
	{
		if ($item->hasField(Item::FIELD_NAME_PRODUCTS) && $item->isChanged(Item::FIELD_NAME_PRODUCTS))
		{
			$productsArray = $item->getProductRows() ? $item->getProductRows()->toArray() : [];

			ExecuteModuleEventEx($event, [$item->getId(), $productsArray]);
		}

		return new Result();
	}
}
