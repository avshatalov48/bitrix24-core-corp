<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class DealActivities extends Deal
{
	use Activity;

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

	public function updateItemStage(int $id, string $stageId, array $newStateParams, array $stages): Result
	{
		$result = $this->getItemViaLoadedItems($id);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$item = $result->getData()['item'];

		$stageCategoryID = DealCategory::resolveFromStageID($stageId);
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

	public function getRequiredFieldsByStages(array $stages): array
	{
		return [];
	}
}
