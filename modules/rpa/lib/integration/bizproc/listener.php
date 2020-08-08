<?php

namespace Bitrix\Rpa\Integration\Bizproc;

use Bitrix\Main;
use Bitrix\Rpa\Model\Item;

class Listener
{
	public static function onItemAdd(Item $item): Main\Result
	{
		if (!self::loadBizproc())
		{
			return (new Main\Result())->addError(new Main\Error('error while loading module bizproc'));
		}

		//run automation
		Automation\Factory::runOnAdd($item->getType()->getId(), $item->getId(), $item->collectValues());

		return new Main\Result();
	}

	public static function onItemUpdate(Item $item, Item $historyItem): Main\Result
	{
		$result = new Main\Result();

		if (!self::loadBizproc())
		{
			return $result->addError(new Main\Error('error while loading module bizproc'));
		}

		if ($historyItem->isChanged('STAGE_ID'))// TODO: check this condition
		{
			//run automation
			Automation\Factory::runOnStatusChanged($item->getType()->getId(), $item->getId(), $item->collectValues());
		}

		return $result;
	}

	public static function onItemDelete(Item $item): Main\Result
	{
		if (!self::loadBizproc())
		{
			return (new Main\Result())->addError(new Main\Error('error while loading module bizproc'));
		}

		if ($item->getId())
		{
			$documentId = Document\Item::makeComplexId($item->getType()->getId(), $item->getId());
			\CBPDocument::OnDocumentDelete($documentId, $errors);
		}

		return new Main\Result();
	}

	private static function loadBizproc(): bool
	{
		return Main\Loader::includeModule('bizproc');
	}
}