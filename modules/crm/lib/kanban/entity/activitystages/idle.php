<?php

namespace Bitrix\Crm\Kanban\Entity\ActivityStages;

use Bitrix\Crm\Activity\Entity\IncomingChannelTable;

class Idle extends AbstractStage
{
	public function getFilterParams(array $filter = []): array
	{
		$this->transformFilter($filter);

		$filter['__CONDITIONS'] = [];
		$filter['__CONDITIONS'][] = [
			'SQL' => "A.DEADLINE = " . \CCrmDateTimeHelper::GetMaxDatabaseDate() . " AND ICT.ACTIVITY_ID is null",
		];

		$filter['__JOINS'] = [
			[
				'TYPE' => 'INNER',
				'SQL' => 'LEFT JOIN ' . IncomingChannelTable::getTableName() . ' AS ICT ON A.ID = ICT.ACTIVITY_ID'
			]
		];

		return $filter;
	}
}