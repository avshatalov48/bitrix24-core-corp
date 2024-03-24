<?php

namespace Bitrix\Crm\Kanban\Entity\ActivityStages;

use Bitrix\Crm\Kanban\Entity\Deadlines\DatePeriods;

class Overdue extends AbstractStage
{
	public function getFilterParams(array $filter = []): array
	{
		$this->transformFilter($filter);

		$filter['<DEADLINE'] = (new DatePeriods())->today();
		$filter['COMPLETED'] = 'N';

		return $filter;
	}
}
