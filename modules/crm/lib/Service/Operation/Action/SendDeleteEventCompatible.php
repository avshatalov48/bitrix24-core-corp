<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Main\Result;

class SendDeleteEventCompatible extends SendEventCompatible
{
	protected function executeEvent(array $event, Item $item): Result
	{
		ExecuteModuleEventEx($event, [$item->getId()]);

		return new Result();
	}
}