<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Result;

class DeleteBizProc extends Action
{
	public function process(Item $item): Result
	{
		$itemBeforeSave = $this->getItemBeforeSave();
		if (!$itemBeforeSave)
		{
			return \Bitrix\Crm\Result::fail('Item before save is required in ' . static::class);
		}

		$bizProc = new \CCrmBizProc(\CCrmOwnerType::ResolveName($itemBeforeSave->getEntityTypeId()));

		$bizProc->processDeletion($itemBeforeSave->getId());

		return new Result();
	}
}
