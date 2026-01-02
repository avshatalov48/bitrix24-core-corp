<?php

namespace Bitrix\Crm\Statistics\OperationFacade;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Conversion\LeadConverter;
use Bitrix\Crm\History;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Statistics;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

final class Lead extends Statistics\OperationFacade
{
	private History\StageHistory\AbstractStageHistory $stageHistory;
	private History\StageHistoryWithSupposed\AbstractStageHistoryWithSupposed $supposedHistory;
	private string $successfulStageId;

	public function __construct(Factory $factory, string $successfulStageId)
	{
		$this->stageHistory = new History\StageHistory\LeadStageHistory($factory);
		$this->supposedHistory = new History\StageHistoryWithSupposed\LeadStageHistoryWithSupposed(
			new History\StageHistoryWithSupposed\TransitionsCalculator($factory),
		);
		$this->successfulStageId = $successfulStageId;
	}

	public function add(Item $item): Result
	{
		return $this->registerAdd($item, true);
	}

	public function restore(Item $item): Result
	{
		return $this->registerAdd($item, false);
	}

	private function registerAdd(Item $item, bool $isNew): Result
	{
		$compatibleData = $item->getCompatibleData();

		Statistics\LeadSumStatisticEntry::register($item->getId(), $compatibleData);

		$commonDiff = ComparerBase::compareEntityFields([], $item->getData());
		$this->stageHistory->registerItemAdd($commonDiff);
		$this->supposedHistory->registerItemAdd($commonDiff);

		if ($item->getStageId() === $this->successfulStageId)
		{
			Statistics\LeadConversionStatisticsEntry::register($item->getId(), $compatibleData, ['IS_NEW' => $isNew]);
		}

		return new Result();
	}

	public function update(Item $itemBeforeSave, Item $item): Result
	{
		$compatibleData = $item->getCompatibleData();

		$commonDiff = ComparerBase::compareEntityFields($itemBeforeSave->getData(Values::ACTUAL), $item->getData());

		Statistics\LeadSumStatisticEntry::register($item->getId(), $compatibleData);
		$this->stageHistory->registerItemUpdate($commonDiff);
		$this->supposedHistory->registerItemUpdate($commonDiff);
		Integration\Channel\LeadChannelBinding::synchronize($item->getId(), $compatibleData);

		$previousStageId = $itemBeforeSave->remindActual(Item::FIELD_NAME_STAGE_ID);
		$currentStageId = $item->getStageId();

		if ($previousStageId !== $currentStageId)
		{
			$wasMovedToSuccessfulStage =
				$currentStageId === $this->successfulStageId
				&& $previousStageId !== $this->successfulStageId
			;
			$wasMovedFromSuccessfulStage =
				$currentStageId !== $this->successfulStageId
				&& $previousStageId === $this->successfulStageId
			;

			if ($wasMovedFromSuccessfulStage)
			{
				$converter = new LeadConverter();
				$converter->setEntityID($item->getId());

				// conversion statistics counts converted deals, contacts and companies
				// they should be unbound before statistics registration
				$converter->unbindChildEntities();
			}

			if ($wasMovedToSuccessfulStage || $wasMovedFromSuccessfulStage)
			{
				Statistics\LeadConversionStatisticsEntry::register($item->getId(), $compatibleData, ['IS_NEW' => false]);
			}
		}

		return new Result();
	}

	public function delete(Item $itemBeforeDeletion): Result
	{
		$this->stageHistory->registerItemDelete($itemBeforeDeletion->getId());
		$this->supposedHistory->registerItemDelete($itemBeforeDeletion->getId());
		Statistics\LeadSumStatisticEntry::unregister($itemBeforeDeletion->getId());
		Statistics\LeadActivityStatisticEntry::unregister($itemBeforeDeletion->getId());
		Integration\Channel\LeadChannelBinding::unregisterAll($itemBeforeDeletion->getId());

		if ($itemBeforeDeletion->getStageId() === $this->successfulStageId)
		{
			Statistics\LeadConversionStatisticsEntry::unregister($itemBeforeDeletion->getId());
		}

		return new Result();
	}
}
