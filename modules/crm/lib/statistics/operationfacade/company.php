<?php

namespace Bitrix\Crm\Statistics\OperationFacade;

use Bitrix\Crm\Item;
use Bitrix\Crm\Statistics;
use Bitrix\Main\Result;

final class Company extends Statistics\OperationFacade
{
	public function add(Item $item): Result
	{
		if ($item->getLeadId() > 0)
		{
			Statistics\LeadConversionStatisticsEntry::processBindingsChange($item->getLeadId());
		}

		Statistics\CompanyGrowthStatisticEntry::register($item->getId(), $item->getCompatibleData());

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

		Statistics\CompanyGrowthStatisticEntry::synchronize($item->getId(),$compatibleData);
		\Bitrix\Crm\Activity\CommunicationStatistics::synchronizeByOwner(
			$item->getEntityTypeId(),
			$item->getId(),
			$compatibleData,
		);

		return new Result();
	}

	public function delete(Item $itemBeforeDeletion): Result
	{
		if ($itemBeforeDeletion->getLeadId())
		{
			Statistics\LeadConversionStatisticsEntry::processBindingsChange($itemBeforeDeletion->getLeadId());
		}

		Statistics\CompanyGrowthStatisticEntry::unregister($itemBeforeDeletion->getId());

		return new Result();
	}
}
