<?php

namespace Bitrix\Crm\Service\Operation\Action\Compatible\SendEvent;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action\Compatible;
use Bitrix\Main\Result;

class Delete extends Compatible\SendEvent
{
	protected function executeEvent(array $event, Item $item): Result
	{
		$itemBeforeSave = $this->getItemBeforeSave();

		if ($itemBeforeSave)
		{
			ExecuteModuleEventEx($event, [$itemBeforeSave->getId()]);
		}

		return new Result();
	}
}
