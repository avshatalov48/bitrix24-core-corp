<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

final class ReadyTodoCountable implements DateFilter
{
	private QueryParts\DeadlineBounds $deadlineBounds;

	public function __construct()
	{
		$this->deadlineBounds = new QueryParts\DeadlineBounds();
	}

	public function applyFilter(ConditionTree $ct, QueryParams $params): void
	{

		$interval = 'PT' . DateFilter::LIGHT_TIME_COMPATIBLE_OFFSET_MINUTES .  'M';
		$lightCounterAt = Dates::now($params);
		$lightCounterAt->add($interval);

		$todayDayStart = Dates::beginOfCurrentDay($params);

		$ct->where('ref.ACTIVITY_DEADLINE', '<=', $lightCounterAt);
		$ct->where('ref.ACTIVITY_DEADLINE', '>=', $todayDayStart);


		if ($params->periodFrom() && $params->periodTo())
		{
			$this->deadlineBounds->applyFilerToField('ref.ACTIVITY_DEADLINE', $ct, $params);
		}
	}
}
