<?php

namespace Bitrix\Crm\Statistics\OperationFacade;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\History;
use Bitrix\Crm\Integration\Channel\DealChannelBinding;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Statistics;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

final class Deal extends Statistics\OperationFacade
{
	private History\StageHistory\AbstractStageHistory $stageHistory;
	private History\StageHistoryWithSupposed\AbstractStageHistoryWithSupposed $supposedStageHistory;

	public function __construct(Factory $factory)
	{
		$this->stageHistory = new History\StageHistory\DealStageHistory($factory);
		$this->supposedStageHistory = new History\StageHistoryWithSupposed\DealStageHistoryWithSupposed(
			new History\StageHistoryWithSupposed\TransitionsCalculator($factory),
		);
	}

	public function add(Item $item): Result
	{
		if ($item->getIsRecurring())
		{
			return new Result();
		}

		$compatibleData = $item->getCompatibleData();
		$commonDiff = ComparerBase::compareEntityFields([], $item->getData());

		Statistics\DealSumStatisticEntry::register($item->getId(), $compatibleData);
		Statistics\DealInvoiceStatisticEntry::synchronize($item->getId(), $compatibleData);
		$this->stageHistory->registerItemAdd($commonDiff);
		$this->supposedStageHistory->registerItemAdd($commonDiff);

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

		Statistics\DealInvoiceStatisticEntry::synchronize($item->getId(), $compatibleData);
		Statistics\DealActivityStatisticEntry::synchronize($item->getId(), $compatibleData);
		DealChannelBinding::synchronize($item->getId(), $compatibleData);

		$commonDiff = ComparerBase::compareEntityFields(
			$itemBeforeSave->getData(Values::ACTUAL),
			$item->getData(),
		);

		$this->stageHistory->registerItemUpdate($commonDiff);
		$this->supposedStageHistory->registerItemUpdate($commonDiff);

		if ($commonDiff->isChanged(Item::FIELD_NAME_LEAD_ID))
		{
			$previousLeadId = $commonDiff->getPreviousValue(Item::FIELD_NAME_LEAD_ID);
			if ($previousLeadId > 0)
			{
				Statistics\LeadConversionStatisticsEntry::processBindingsChange($previousLeadId);
			}

			$currentLeadId = $commonDiff->getCurrentValue(Item::FIELD_NAME_LEAD_ID);
			if ($currentLeadId > 0)
			{
				Statistics\LeadConversionStatisticsEntry::processBindingsChange($currentLeadId);
			}
		}

		if ($commonDiff->isChanged(Item::FIELD_NAME_CATEGORY_ID))
		{
			Statistics\DealSumStatisticEntry::processCagegoryChange($item->getId());
			Statistics\DealInvoiceStatisticEntry::processCagegoryChange($item->getId());
			Statistics\DealActivityStatisticEntry::processCagegoryChange($item->getId());
		}

		return new Result();
	}

	public function delete(Item $itemBeforeDeletion): Result
	{
		$this->stageHistory->registerItemDelete($itemBeforeDeletion->getId());
		$this->supposedStageHistory->registerItemDelete($itemBeforeDeletion->getId());
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
