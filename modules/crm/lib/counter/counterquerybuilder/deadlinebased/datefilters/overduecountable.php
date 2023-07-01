<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

final class OverdueCountable implements DateFilter
{
	public function applyFilter(ConditionTree $ct, QueryParams $params): void
	{
		$beginOfCurrDay = Dates::beginOfCurrentDay($params);

		$ct->where('ref.ACTIVITY_DEADLINE', '<', $beginOfCurrDay);
	}
}