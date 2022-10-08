<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation;
use Bitrix\Main\Result;

class DeleteQuoteFiles extends Operation\Action
{
	/**
	 * @param Item\Quote $item
	 * @return Result
	 */
	public function process(Item $item): Result
	{
		$result = new Result();

		$elements = $item->getStorageElementIds();
		if(!empty($elements))
		{
			foreach($elements as $elementId)
			{
				StorageManager::deleteFile($elementId, (int)$item->getStorageTypeId());
			}
		}

		return $result;
	}
}
