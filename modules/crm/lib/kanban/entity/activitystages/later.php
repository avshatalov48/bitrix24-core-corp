<?php

namespace Bitrix\Crm\Kanban\Entity\ActivityStages;

use Bitrix\Crm\Kanban\Entity\Deadlines\DatePeriods;
use Bitrix\Main\Type\Date;

class Later extends AbstractStage
{
	public function getFilterParams(array $filter = []): array
	{
		$this->transformFilter($filter);

		$filter['>=DEADLINE'] = (new DatePeriods())->afterNextWeek();
		$filter['<=DEADLINE'] = new Date('9999-12-31', 'Y-m-d');
		$filter['COMPLETED'] = 'N';

		return $filter;
	}
}