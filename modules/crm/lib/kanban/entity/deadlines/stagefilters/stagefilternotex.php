<?php

namespace Bitrix\Crm\Kanban\Entity\Deadlines\Stagefilters;

use Bitrix\Crm\Kanban\Entity\Deadlines\DatePeriods;
use Bitrix\Crm\Kanban\Entity\Deadlines\DeadlinesStageManager;
use Bitrix\Crm\Kanban\Entity\Deadlines\FilterDateMerger;

final class StageFilterNotEx implements StageFilter
{
	private DatePeriods $datePeriods;

	private FilterDateMerger $dateMerger;

	public function __construct(DatePeriods $datePeriods, FilterDateMerger $dateMerger)
	{
		$this->datePeriods = $datePeriods;
		$this->dateMerger = $dateMerger;
	}

	public function applyFilter(string $stage, array $filter, string $fieldName): array
	{
		$stageFilter = [];
		switch ($stage)
		{
			case DeadlinesStageManager::STAGE_OVERDUE:
				$stageFilter["<$fieldName"] = $this->datePeriods->today();
				break;
			case DeadlinesStageManager::STAGE_TODAY:
				$stageFilter["=$fieldName"] = $this->datePeriods->today();
				break;
			case DeadlinesStageManager::STAGE_THIS_WEEK:
				$stageFilter[">=$fieldName"] = $this->datePeriods->tomorrow();
				$stageFilter["<=$fieldName"] = $this->datePeriods->currentWeekLastDay();
				break;
			case DeadlinesStageManager::STAGE_NEXT_WEEK:
				$stageFilter[">=$fieldName"] = $this->datePeriods->nextWeekFirstDay();
				$stageFilter["<=$fieldName"] = $this->datePeriods->nextWeekLastDay();
				break;
			case DeadlinesStageManager::STAGE_LATER:
				$stageFilter['__INNER_FILTER_StageFilterNotEx_1'] = [
					'LOGIC' => 'OR',
					">=$fieldName" => $this->datePeriods->afterNextWeek(),
					"=$fieldName" => false
				];
				break;
			default:
				return $stageFilter;
		}
		return $this->dateMerger->merge($filter, $stageFilter, $fieldName);
	}
}