<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\QuoteTable;
use Bitrix\Main\Result;

class QuoteAttachedFilesCreate extends QuoteAttachedFiles
{
	/**
	 * @param Item\Quote $item
	 * @return Result
	 */
	public function process(Item $item): Result
	{
		$result = $this->attachFiles($item, true);

		QuoteTable::update($item->getId(), ['STORAGE_ELEMENT_IDS' => $item->getStorageElementIds()]);

		return $result;
	}
}