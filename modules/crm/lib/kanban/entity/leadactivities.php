<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Main\Result;

class LeadActivities extends Lead
{
	use ActivityTrait;

	public function isExclusionSupported(): bool
	{
		return false;
	}

	public function isTotalPriceSupported(): bool
	{
		return false;
	}

	public function updateItemStage(int $id, string $stageId, array $newStateParams, array $stages): Result
	{
		$result = $this->getItemViaLoadedItems($id);
		if (!$result->isSuccess())
		{
			return $result;
		}

		return $this->changeStageByActivity($stageId, $id);
	}

	public function getRequiredFieldsByStages(array $stages): array
	{
		return [];
	}

	public function getAllowStages(array $filter = []): array
	{
		return array_column($this->getStagesList(), 'STATUS_ID');
	}
}
