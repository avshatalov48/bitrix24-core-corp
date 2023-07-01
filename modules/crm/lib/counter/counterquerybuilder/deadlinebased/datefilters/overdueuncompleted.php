<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters;


use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Type\DateTime;

final class OverdueUncompleted implements DateFilter
{
	public function applyFilter(ConditionTree $ct, QueryParams $params): void
	{
		$endOfCurDate = Dates::endOfCurrentDay($params);
		$endOfCurDate->add('-1 day');

		$ct->where('ref.MIN_DEADLINE', '<=', $endOfCurDate);
	}
}
