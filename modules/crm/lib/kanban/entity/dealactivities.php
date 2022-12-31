<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class DealActivities extends Deal
{
	use Activity;

	public function getStageFieldName(): string
	{
		return EntityActivities::ACTIVITY_STAGE_ID;
	}

	public function fillStageTotalSums(array $filter, array $runtime, array &$stages): void
	{
		foreach ($stages as &$stage)
		{
			$stage['count'] = $this->getEntityActivities()->calculateTotalForStage($stage['id'], $filter);
		}
	}

	public function getItems(array $parameters): \CDBResult
	{
		$parameters = $this->getEntityActivities()->prepareItemsListParams($parameters);

		$columnId = $parameters['columnId'] ?? '';
		$filter = $parameters['filter'] ?? [];
		return $this->getEntityActivities()->prepareItemsResult($columnId, parent::getItems($parameters), $filter);
	}

	protected function getEntityActivities(): EntityActivities
	{
		if (!$this->entityActivities)
		{
			$this->entityActivities = new EntityActivities($this->getTypeId(), $this->getCategoryId());
		}

		return $this->entityActivities;
	}

	public function isTotalPriceSupported(): bool
	{
		return false;
	}

	public function isRecurringSupported(): bool
	{
		return false; // @todo check this
	}

	public function isExclusionSupported(): bool
	{
		return false; // @todo check this
	}

	public function applyCountersFilter(array &$filter): void
	{
		// do nothing, $filter['ACTIVITY_COUNTER'] will be applied in $this->getItems()
	}

	public function updateItemStage(int $id, string $stageId, array $newStateParams, array $stages): Result
	{
		$result = $this->getItemViaLoadedItems($id);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$item = $result->getData()['item'];

		$stageCategoryID = (int) DealCategory::resolveFromStageID($stageId);
		$dealCategoryID = (int) $item['CATEGORY_ID'];
		if($dealCategoryID !== $stageCategoryID && $this->getCategoryId() >= 0)
		{
			return $result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_DEAL_STAGE_MISMATCH')));
		}

		return $this->changeStageByActivity($stageId, $id);
	}

	public function canUseAllCategories(): bool
	{
		return true;
	}
}
