<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Integration\Disk\Dto\SaveAOParam;
use Bitrix\Crm\Integration\Disk\QuoteItemAttachedObjectPersist;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

abstract class QuoteAttachedFiles extends Action
{

	protected function attachFiles(Item $item, bool $isNew): Result
	{
		$result = new Result();

		if (!$item instanceof Item\Quote || !$item->hasField('STORAGE_ELEMENT_IDS'))
		{
			$result->addError(new Error('Filed `STORAGE_ELEMENT_IDS` does not exist'));

			return $result;
		}

		$currentIds = $item->get('STORAGE_ELEMENT_IDS');
		if ($currentIds === null)
		{
			$currentIds = [];
		}

		$currentUserId = Container::getInstance()->getContext()->getUserId();

		$param = new SaveAOParam(
			$item->getId(),
			$isNew ? [] : $item->remindActual('STORAGE_ELEMENT_IDS'),
			$currentIds,
			$currentUserId
		);

		$valuesToInsert = QuoteItemAttachedObjectPersist::getInstance()
			->saveAllAsAttachedObject($param);

		$item->set('STORAGE_ELEMENT_IDS', $valuesToInsert);

		return $result;
	}
}