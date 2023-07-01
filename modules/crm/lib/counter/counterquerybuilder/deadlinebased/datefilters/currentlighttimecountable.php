<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

final class CurrentLightTimeCountable implements DateFilter
{
	private QueryParts\DeadlineBounds $deadlineBounds;

	public function __construct()
	{
		$this->deadlineBounds = new QueryParts\DeadlineBounds();
	}

	public function applyFilter(ConditionTree $ct, QueryParams $params): void
	{
		$now = Dates::now($params);
		$ct->where('ref.LIGHT_COUNTER_AT', '<=', $now);

		if ($params->periodFrom() && $params->periodTo())
		{
			$this->deadlineBounds->applyFilerToField('ref.ACTIVITY_DEADLINE', $ct, $params);
		}
	}
}