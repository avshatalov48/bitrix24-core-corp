<?php

namespace Bitrix\Crm\Kanban\Entity\ActivityStages;

use Bitrix\Crm\Kanban\Entity\Deadlines\DatePeriods;

class Week extends AbstractStage
{
	public function getFilterParams(array $filter = []): array
	{
		$this->transformFilter($filter);

		$filter['>=DEADLINE'] = (new DatePeriods())->tomorrow();
		$filter['<=DEADLINE'] = (new DatePeriods())->currentWeekLastDay();
		$filter['COMPLETED'] = 'N';

		return $filter;
	}
}
