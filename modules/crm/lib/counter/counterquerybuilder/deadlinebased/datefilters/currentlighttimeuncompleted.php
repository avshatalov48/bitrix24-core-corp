<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

final class CurrentLightTimeUncompleted implements DateFilter
{
	private QueryParts\DeadlineBounds $deadlineBounds;

	public function __construct()
	{
		$this->deadlineBounds = new QueryParts\DeadlineBounds();
	}

	public function applyFilter(ConditionTree $ct, QueryParams $params): void
	{
		$beginOfCurrDay = Dates::beginOfCurrentDay($params);
		$now = Dates::now($params);

		$groupCt = new ConditionTree();
		$groupCt->logic(ConditionTree::LOGIC_OR);

		$groupCt->addCondition(
			(new ConditionTree())->where('ref.MIN_DEADLINE', '<', $beginOfCurrDay)
		);
		$groupCt->addCondition(
			(new ConditionTree())->where('ref.MIN_LIGHT_COUNTER_AT', '<=', $now)
		);

		$ct->addCondition($groupCt);

		if ($params->periodFrom() && $params->periodTo())
		{
			$this->deadlineBounds->applyFilerToField('ref.MIN_DEADLINE', $ct, $params);
		}
	}
}