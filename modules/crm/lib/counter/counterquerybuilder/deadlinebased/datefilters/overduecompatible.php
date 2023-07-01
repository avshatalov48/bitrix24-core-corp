<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Type\DateTime;

final class OverdueCompatible implements DateFilter
{
	public function applyFilter(ConditionTree $ct, QueryParams $params): void
	{
		$endOfCurDate = Dates::endOfCurrentDay($params);
		$endOfCurDate->add('-1 day');

		$ct->where('DEADLINE', '<=', $endOfCurDate);
	}
}
