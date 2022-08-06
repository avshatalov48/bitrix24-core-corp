<?php

namespace Bitrix\Crm\Statistics\OperationFacade;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\History;
use Bitrix\Crm\Integration\Channel\DealChannelBinding;
use Bitrix\Crm\Item;
use Bitrix\Crm\Statistics;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

final class Deal extends Statistics\OperationFacade
{
	public function add(Item $item): Result
	{
		if ($item->getIsRecurring())
		{
			return new Result();
		}

		$compatibleData = $item->getCompatibleData();

		Statistics\DealSumStatisticEntry::register($item->getId(), $compatibleData);
		Statistics\DealInvoiceStatisticEntry::synchronize($item->getId(), $compatibleData);
		History\DealStageHistoryEntry::register($item->getId(), $compatibleData, ['IS_NEW' => true]);

		if ($item->getLeadId() > 0)
		{
			Statistics\LeadConversionStatisticsEntry::processBindingsChange($item->getLeadId());
		}

		return new Result();
	}

	public function update(Item $itemBeforeSave, Item $item): Result
	{
		if ($item->getIsRecurring())
		{
			return new Result();
		}

		$compatibleData = $item->getCompatibleData();

		Statistics\DealSumStatisticEntry::register($item->getId(), $compatibleData);
		History\DealStageHistoryEntry::synchronize($item->getId(), $compatibleData);
		Statistics\DealInvoiceStatisticEntry::synchronize($item->getId(), $compatibleData);
		Statistics\DealActivityStatisticEntry::synchronize($item->getId(), $compatibleData);
		DealChannelBinding::synchronize($item->getId(), $compatibleData);
		History\DealStageHistoryEntry::register($item->getId(), $compatibleData, ['IS_NEW' => false]);

		$difference = ComparerBase::compareEntityFields(
			$itemBeforeSave->getData(Values::ACTUAL),
			$item->getData(),
		);

		if ($difference->isChanged(Item::FIELD_NAME_LEAD_ID))
		{
			$previousLeadId = $difference->getPreviousValue(Item::FIELD_NAME_LEAD_ID);
			if ($previousLeadId > 0)
			{
				Statistics\LeadConversionStatisticsEntry::processBindingsChange($previousLeadId);
			}

			$currentLeadId = $difference->getCurrentValue(Item::FIELD_NAME_LEAD_ID);
			if ($currentLeadId > 0)
			{
				Statistics\LeadConversionStatisticsEntry::processBindingsChange($currentLeadId);
			}
		}

		if ($difference->isChanged(Item::FIELD_NAME_CATEGORY_ID))
		{
			History\DealStageHistoryEntry::processCagegoryChange($item->getId());
			Statistics\DealSumStatisticEntry::processCagegoryChange($item->getId());
			Statistics\DealInvoiceStatisticEntry::processCagegoryChange($item->getId());
			Statistics\DealActivityStatisticEntry::processCagegoryChange($item->getId());
		}

		return new Result();
	}

	public function delete(Item $itemBeforeDeletion): Result
	{
		History\DealStageHistoryEntry::unregister($itemBeforeDeletion->getId());
		Statistics\DealSumStatisticEntry::unregister($itemBeforeDeletion->getId());
		Statistics\DealInvoiceStatisticEntry::unregister($itemBeforeDeletion->getId());
		Statistics\DealActivityStatisticEntry::unregister($itemBeforeDeletion->getId());
		DealChannelBinding::unregisterAll($itemBeforeDeletion->getId());

		if ($itemBeforeDeletion->getLeadId() > 0)
		{
			Statistics\LeadConversionStatisticsEntry::processBindingsChange($itemBeforeDeletion->getLeadId());
		}

		return new Result();
	}
}
