<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\RepeatSale\Segment\SegmentManager;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Result;

final class DeleteRepeatSaleLog extends Action
{
	public function process(Item $item): Result
	{
		$entityTypeId = $item->getEntityTypeId();
		$entityId = $this->getItemBeforeSave()->getId();
		SegmentManager::onEntityDelete(new ItemIdentifier($entityTypeId, $entityId));

		return new Result();
	}
}
