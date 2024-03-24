<?php

namespace Bitrix\Crm\Kanban\Entity\ActivityStages;

use Bitrix\Crm\Kanban\Entity\Deadlines\DatePeriods;

class NextWeek extends AbstractStage
{
	public function getFilterParams(array $filter = []): array
	{
		$this->transformFilter($filter);

		$filter['>=DEADLINE'] = (new DatePeriods())->nextWeekFirstDay();
		$filter['<=DEADLINE'] = (new DatePeriods())->nextWeekLastDay();
		$filter['COMPLETED'] = 'N';

		return $filter;
	}
}
