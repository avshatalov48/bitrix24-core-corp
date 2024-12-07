<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Integration\Disk\DiskRepository;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation;
use Bitrix\Main\Config\Option;
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
			if (!$this->isFileConvertMode()) // normal mode
			{
				DiskRepository::getInstance()->detachByAttachedObjectIds($elements);
			}
			else
			{
				$this->deleteByQuoteId($item);

			}
		}

		return $result;
	}

	private function isFileConvertMode(): bool
	{
		return Option::get('crm', 'quote_storage_element_ids_convert_progress', 'N') === 'Y';
	}

	private function deleteByQuoteId(Item $item): void
	{
		$id = $item->primary['ID'] ?? null;

		if (!$id)
		{
			return;
		}

		DiskRepository::getInstance()->detachAttachedObjectByQuote($id);
	}
}
