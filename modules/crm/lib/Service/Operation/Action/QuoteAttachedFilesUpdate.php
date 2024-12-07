<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Main\Result;

class QuoteAttachedFilesUpdate extends QuoteAttachedFiles
{
	public function process(Item $item): Result
	{
		$result = new Result();

		if (!$item->isChanged('STORAGE_ELEMENT_IDS'))
		{
			return $result;
		}

		return $this->attachFiles($item, false);
	}
}