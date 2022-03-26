<?php

namespace Bitrix\Crm\Service\Operation\Action\Compatible\SendEvent;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action\Compatible;
use Bitrix\Main\Result;

class ExternalAdd extends Compatible\SendEvent
{
	public function process(Item $item): Result
	{
		if ($item->hasField(Item::FIELD_NAME_ORIGIN_ID) && !empty($item->getOriginId()))
		{
			return parent::process($item);
		}

		return new Result();
	}
}
