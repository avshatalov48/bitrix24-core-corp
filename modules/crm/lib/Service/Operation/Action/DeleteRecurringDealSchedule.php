<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation;
use Bitrix\Main\Result;

class DeleteRecurringDealSchedule extends Operation\Action {
	public function process(Item $item): Result
	{
		$itemBeforeSave = $this->getItemBeforeSave();
		if ($itemBeforeSave && $itemBeforeSave->getIsRecurring())
		{
			$dealRecurringItem = \Bitrix\Crm\Recurring\Entity\Item\DealExist::loadByDealId($itemBeforeSave->getId());
			if ($dealRecurringItem)
			{
				return $dealRecurringItem->delete();
			}
		}

		return new Result();
	}
}
