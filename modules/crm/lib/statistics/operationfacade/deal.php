<?php

namespace Bitrix\Crm\Statistics\OperationFacade;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\History;
use Bitrix\Crm\Integration\Channel\DealChannelBinding;
use Bitrix\Crm\Item;
use Bitrix\Crm\Statistics;
use Bitrix\Crm\Statistics\OperationFacade;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

class Deal extends OperationFacade
{
	public function add(Item $item): Result
	{
		if ($item->getIsRecurring())
		{
			return new Result();
		}

		Statistics\DealSumStatisticEntry::register($item->getId(), $item->getCompatibleData());
		Statistics\DealInvoiceStatisticEntry::synchronize($item->getId(), $item->getCompatibleData());
		History\DealStageHistoryEntry::register($item->getId(), $item->getCompatibleData(), ['IS_NEW' => true]);

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

		Statistics\DealSumStatisticEntry::register($item->getId(), $item->getCompatibleData());
		History\DealStageHistoryEntry::synchronize($item->getId(), $item->getCompatibleData());
		Statistics\DealInvoiceStatisticEntry::synchronize($item->getId(), $item->getCompatibleData());
		Statistics\DealActivityStatisticEntry::synchronize($item->getId(), $item->getCompatibleData());
		DealChannelBinding::synchronize($item->getId(), $item->getCompatibleData());
		History\DealStageHistoryEntry::register($item->getId(), $item->getCompatibleData(), ['IS_NEW' => false]);

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
