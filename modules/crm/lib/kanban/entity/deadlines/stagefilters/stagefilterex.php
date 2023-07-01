<?php

namespace Bitrix\Crm\Kanban\Entity\Deadlines\Stagefilters;

use Bitrix\Crm\Kanban\Entity\Deadlines\DatePeriods;
use Bitrix\Crm\Kanban\Entity\Deadlines\DeadlinesStageManager;

final class StageFilterEx implements StageFilter
{
	private DatePeriods $datePeriods;

	public function __construct(DatePeriods $datePeriods)
	{
		$this->datePeriods = $datePeriods;
	}

	public function applyFilter(string $stage, array $filter, string $fieldName): array
	{
		switch ($stage)
		{
			case DeadlinesStageManager::STAGE_OVERDUE:
				$filter[] = ["<$fieldName" => $this->datePeriods->today()];
				break;
			case DeadlinesStageManager::STAGE_TODAY:
				$filter[] = ["=$fieldName" => $this->datePeriods->today()];
				break;
			case DeadlinesStageManager::STAGE_THIS_WEEK:
				$filter[] = [
					">=$fieldName" => $this->datePeriods->tomorrow(),
					"<=$fieldName" => $this->datePeriods->currentWeekLastDay()
				];
				break;
			case DeadlinesStageManager::STAGE_NEXT_WEEK:
				$filter[] = [
					">=$fieldName" => $this->datePeriods->nextWeekFirstDay(),
					"<=$fieldName" => $this->datePeriods->nextWeekLastDay()
				];
				break;
			case DeadlinesStageManager::STAGE_LATER:
				$filter[] = [
					'LOGIC' => 'OR',
					">=$fieldName" => $this->datePeriods->afterNextWeek(),
					"=$fieldName" => false
				];
				break;
			default:
				return $filter;
		}
		return $filter;
	}
}