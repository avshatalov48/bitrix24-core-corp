<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Result;

class ClearDefaultMyCompanyCache extends Action
{
	public function process(Item $item): Result
	{
		EntityLink::clearMyCompanyCache();

		return new Result();
	}
}
