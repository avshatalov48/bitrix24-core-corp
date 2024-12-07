<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Entity\CommentsHelper;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Model\FieldContentTypeTable;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;
use Bitrix\Main\Result;

final class Comments extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if ($item->isChanged($this->getName()))
		{
			$newComment = $item->get($this->getName());

			$item->set(
				$this->getName(),
				CommentsHelper::normalizeComment($newComment, ['p']),
			);
		}

		return new Result();
	}

	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		if ($itemBeforeSave->isNew() || $itemBeforeSave->remindActual($this->getName()) !== $item->get($this->getName()))
		{
			$identifier = ItemIdentifier::createByItem($item);
			$contentTypes = FieldContentTypeTable::loadForItem($identifier);

			//todo optimize for quote?
			$contentTypes[$this->getName()] = \CCrmContentType::BBCode;
			FieldContentTypeTable::saveForItem($identifier, $contentTypes);
		}

		return new FieldAfterSaveResult();
	}
}
