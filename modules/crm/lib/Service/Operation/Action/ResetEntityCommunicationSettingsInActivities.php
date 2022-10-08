<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

class ResetEntityCommunicationSettingsInActivities extends Operation\Action
{
	public function process(Item $item): Result
	{
		$itemBeforeSave = $this->getItemBeforeSave();
		if (!$itemBeforeSave)
		{
			return new Result();
		}

		$difference = ComparerBase::compareEntityFields(
			$itemBeforeSave->getData(Values::ACTUAL),
			$item->getData(),
		);

		if (
			$difference->isChanged(Item::FIELD_NAME_NAME)
			|| $difference->isChanged(Item::FIELD_NAME_SECOND_NAME)
			|| $difference->isChanged(Item::FIELD_NAME_LAST_NAME)
			|| $difference->isChanged(Item::FIELD_NAME_HONORIFIC)
		)
		{
			\CCrmActivity::ResetEntityCommunicationSettings($item->getEntityTypeId(), $item->getId());
		}

		return new Result();
	}
}
