<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Result;

class ClearEntitySelectorPrepareRequisiteDataCache extends Action
{
	public function process(Item $item): Result
	{
		\CCrmEntitySelectorHelper::clearPrepareRequisiteDataCacheByEntity(
			$item->getEntityTypeId(),
			(int)$item->primary['ID']
		);

		return new Result();
	}
}
