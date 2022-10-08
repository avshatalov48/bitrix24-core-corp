<?php

namespace Bitrix\Crm\Service\Operation\Action\Compatible\SendEvent;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action\Compatible;
use Bitrix\Main\Result;

class ProductRowsSave extends Compatible\SendEvent
{
	protected function executeEvent(array $event, Item $item): Result
	{
		if ($this->eventName === 'OnBeforeCrmDealProductRowsSave')
		{
			return $this->executeEventBeforeSave($event, $item);
		}

		return $this->executeEventAfterSave($event, $item);
	}

	/**
	 * Process after save event.
	 *
	 * @param array $event
	 * @param Item $item
	 *
	 * @return Result
	 */
	private function executeEventAfterSave(array $event, Item $item): Result
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

	/**
	 * Process before save event.
	 *
	 * @param array $event
	 * @param Item $item
	 *
	 * @return Result
	 */
	private function executeEventBeforeSave(array $event, Item $item): Result
	{
		if ($item->hasField(Item::FIELD_NAME_PRODUCTS))
		{
			$productsArray = $item->getProductRows() ? $item->getProductRows()->toArray() : [];

			$eventResult = ExecuteModuleEventEx($event, [$item->getId(), $productsArray]);
			if ($eventResult instanceof Result)
			{
				return $eventResult;
			}
		}

		return new Result();
	}
}
