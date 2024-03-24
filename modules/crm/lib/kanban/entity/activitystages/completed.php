<?php

namespace Bitrix\Crm\Kanban\Entity\ActivityStages;

class Completed extends AbstractStage
{
	public function getFilterParams(array $filter = []): array
	{
		$this->transformFilter($filter);

		$filter['COMPLETED'] = 'Y';

		return $filter;
	}
}