<?php

namespace Bitrix\Crm\Statistics\OperationFacade;

use Bitrix\Crm\Conversion\LeadConverter;
use Bitrix\Crm\History;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Item;
use Bitrix\Crm\Statistics;
use Bitrix\Main\Result;

final class Lead extends Statistics\OperationFacade
{
	/** @var string */
	private $successfulStageId;

	public function __construct(string $successfulStageId)
	{
		$this->successfulStageId = $successfulStageId;
	}

	public function add(Item $item): Result
	{
		return $this->registerStatistics($item, true);
	}

	public function restore(Item $item): Result
	{
		return $this->registerStatistics($item, false);
	}

	private function registerStatistics(Item $item, bool $isNew): Result
	{
		$compatibleData = $item->getCompatibleData();

		Statistics\LeadSumStatisticEntry::register($item->getId(), $compatibleData);
		History\LeadStatusHistoryEntry::register($item->getId(), $compatibleData, ['IS_NEW' => $isNew]);

		if ($item->getStageId() === $this->successfulStageId)
		{
			Statistics\LeadConversionStatisticsEntry::register($item->getId(), $compatibleData, ['IS_NEW' => $isNew]);
		}

		return new Result();
	}

	public function update(Item $itemBeforeSave, Item $item): Result
	{
		$compatibleData = $item->getCompatibleData();

		Statistics\LeadSumStatisticEntry::register($item->getId(), $compatibleData);
		History\LeadStatusHistoryEntry::synchronize($item->getId(), $compatibleData);
		Integration\Channel\LeadChannelBinding::synchronize($item->getId(), $compatibleData);

		$previousStageId = $itemBeforeSave->remindActual(Item::FIELD_NAME_STAGE_ID);
		$currentStageId = $item->getStageId();

		if ($previousStageId !== $currentStageId)
		{
			History\LeadStatusHistoryEntry::register($item->getId(), $compatibleData, ['IS_NEW' => false]);

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
		History\LeadStatusHistoryEntry::unregister($itemBeforeDeletion->getId());
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
