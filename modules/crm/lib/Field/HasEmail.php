<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;
use Bitrix\Main\Result;

class HasEmail extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if (!$item->isNew())
		{
			return new Result();
		}

		if ($item->hasFm())
		{
			$hasEmail = \CCrmFieldMulti::HasValues($item->getFm()->toArray(), \CCrmFieldMulti::EMAIL);

			$item->set($this->getName(), $hasEmail);
		}

		return new Result();
	}

	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$result = new FieldAfterSaveResult();

		if ($itemBeforeSave->isNew())
		{
			return $result;
		}

		if ($item->hasFm())
		{
			// we can't simply use FM from item since they may be incomplete
			// e.g., we don't change phones and therefore don't provide them in FM at all
			$multifieldValues = DuplicateCommunicationCriterion::prepareEntityMultifieldValues(
				$item->getEntityTypeId(),
				$item->getId(),
			);

			$hasEmail = \CCrmFieldMulti::HasValues($multifieldValues, \CCrmFieldMulti::EMAIL);

			$result->setNewValue($this->getName(), $hasEmail);
		}

		return $result;
	}
}
