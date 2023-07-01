<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

interface DateFilter
{
	const LIGHT_TIME_COMPATIBLE_OFFSET_MINUTES = 15;

	public function applyFilter(ConditionTree $ct, QueryParams $params): void;
}