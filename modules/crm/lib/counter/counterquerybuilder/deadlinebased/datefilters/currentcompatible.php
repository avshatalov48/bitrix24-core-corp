<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

final class CurrentCompatible implements DateFilter
{
	private QueryParts\DeadlineBounds $deadlineBounds;

	public function __construct()
	{
		$this->deadlineBounds = new QueryParts\DeadlineBounds();
	}

	public function applyFilter(ConditionTree $ct, QueryParams $params): void
	{
		$interval = 'PT' . DateFilter::LIGHT_TIME_COMPATIBLE_OFFSET_MINUTES . 'M';
		$lightCounterAt = Dates::now($params);
		$lightCounterAt->add($interval);

		$ct->where('DEADLINE', '<=', $lightCounterAt);

		if ($params->periodFrom() && $params->periodTo())
		{
			$this->deadlineBounds->applyFilerToField('DEADLINE', $ct, $params);
		}
	}
}