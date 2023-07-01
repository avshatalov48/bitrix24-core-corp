<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

final class ReadyTodoCompatible implements DateFilter
{
	private QueryParts\DeadlineBounds $deadlineBounds;

	public function __construct()
	{
		$this->deadlineBounds = new QueryParts\DeadlineBounds();
	}

	public function applyFilter(ConditionTree $ct, QueryParams $params): void
	{
		$lightCounterAt = Dates::now($params);
		$lightCounterAt->add('PT' . DateFilter::LIGHT_TIME_COMPATIBLE_OFFSET_MINUTES .  'M');

		$todayBegins = Dates::beginOfCurrentDay($params);

		$ct->where('DEADLINE', '<=', $lightCounterAt);
		$ct->where('DEADLINE', '>=', $todayBegins);

		if ($params->periodFrom() && $params->periodTo())
		{
			$this->deadlineBounds->applyFilerToField('DEADLINE', $ct, $params);
		}
	}
}
