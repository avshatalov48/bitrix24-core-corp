<?php

namespace Bitrix\Crm\Statistics\OperationFacade;

use Bitrix\Crm\Item;
use Bitrix\Crm\Statistics;
use Bitrix\Main\Result;

final class Contact extends Statistics\OperationFacade
{
	public function add(Item $item): Result
	{
		Statistics\ContactGrowthStatisticEntry::register($item->getId(), $item->getCompatibleData());
		Statistics\LeadConversionStatisticsEntry::processBindingsChange($item->getLeadId());

		return new Result();
	}

	public function update(Item $itemBeforeSave, Item $item): Result
	{
		$previousLeadId = $itemBeforeSave->remindActual(Item::FIELD_NAME_LEAD_ID);
		$currentLeadId = $item->getLeadId();

		if ($previousLeadId !== $currentLeadId)
		{
			if ($previousLeadId > 0)
			{
				Statistics\LeadConversionStatisticsEntry::processBindingsChange($previousLeadId);
			}

			if ($currentLeadId > 0)
			{
				Statistics\LeadConversionStatisticsEntry::processBindingsChange($currentLeadId);
			}
		}

		$compatibleData = $item->getCompatibleData();

		Statistics\ContactGrowthStatisticEntry::synchronize($item->getId(), $compatibleData);
		\Bitrix\Crm\Activity\CommunicationStatistics::synchronizeByOwner(
			$item->getEntityTypeId(),
			$item->getId(),
			$compatibleData,
		);

		return new Result();
	}

	public function delete(Item $itemBeforeDeletion): Result
	{
		if ($itemBeforeDeletion->getLeadId() > 0)
		{
			Statistics\LeadConversionStatisticsEntry::processBindingsChange($itemBeforeDeletion->getLeadId());
		}

		Statistics\ContactGrowthStatisticEntry::unregister($itemBeforeDeletion->getId());

		return new Result();
	}
}
