<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Statistics;
use Bitrix\Crm\Timeline\TimelineManager;
use Bitrix\Main\Result;

class Restore extends Operation\Add
{
	//todo do not forget to delete usages of \Bitrix\Crm\Recycling\BaseController::startRecoveryWorkflows
	// otherwise launch of this operation will start bizproc twice

	/**
	 * We should be able to explicitly set created, updated and moved data for an item during restoration. Otherwise, we
	 * will lose real info about when an original item (before deletion) was created, etc.
	 *
	 * Therefore, we have this workaround.
	 *
	 * @return Result
	 */
	public function processFieldsBeforeSave(): Result
	{
		$createdTime = $this->item->getCreatedTime();
		$updatedTime = $this->item->getUpdatedTime();
		$movedTime = $this->item->hasField(Item::FIELD_NAME_MOVED_TIME) ? $this->item->getMovedTime() : null;

		$createdBy = $this->item->getCreatedBy();
		$updatedBy = $this->item->getUpdatedBy();
		$movedBy = $this->item->hasField(Item::FIELD_NAME_MOVED_BY) ? $this->item->getMovedBy() : null;

		$result = parent::processFieldsBeforeSave();

		if (!is_null($createdTime))
		{
			$this->item->setCreatedTime($createdTime);
		}

		if (!is_null($updatedTime))
		{
			$this->item->setUpdatedTime($updatedTime);
		}

		if (!is_null($movedTime))
		{
			$this->item->setMovedTime($movedTime);
		}

		if ($createdBy > 0)
		{
			$this->item->setCreatedBy($createdBy);
		}

		if ($updatedBy > 0)
		{
			$this->item->setUpdatedBy($updatedBy);
		}

		if ($movedBy > 0)
		{
			$this->item->setMovedBy($movedBy);
		}

		return $result;
	}

	public function isCheckFieldsEnabled(): bool
	{
		// we don't check user fields at all during restoration
		return false;
	}

	protected function registerStatistics(Statistics\OperationFacade $statisticsFacade): Result
	{
		return $statisticsFacade->restore($this->getItem());
	}

	protected function createTimelineRecord(): void
	{
		$timelineController = TimelineManager::resolveController([
			'ASSOCIATED_ENTITY_TYPE_ID' => $this->item->getEntityTypeId()
		]);

		if ($timelineController)
		{
			$timelineController->onRestore(
				$this->item->getId(),
				[
					'FIELDS' => $this->item->getData(),
					'FIELDS_MAP' => $this->item->getFieldsMap(),
				],
			);
		}
	}
}
