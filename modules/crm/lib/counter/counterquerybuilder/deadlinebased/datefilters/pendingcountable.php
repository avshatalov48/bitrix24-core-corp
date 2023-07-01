<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

final class PendingCountable implements DateFilter
{
	private QueryParts\DeadlineBounds $deadlineBounds;

	public function __construct()
	{
		$this->deadlineBounds = new QueryParts\DeadlineBounds();
	}

	public function applyFilter(ConditionTree $ct, QueryParams $params): void
	{
		$lowBound = $this->deadlineBounds->getLowBound($params);
		$highBound = $this->deadlineBounds->getHighBound($params);

		$sqlHelper = Application::getConnection()->getSqlHelper();

		if ($lowBound)
		{
			$ct->where('ref.ACTIVITY_DEADLINE', '>=', new SqlExpression($sqlHelper->convertToDbDateTime($lowBound)));
		}
		if ($highBound)
		{
			$ct->where('ref.ACTIVITY_DEADLINE', '<=', new SqlExpression($sqlHelper->convertToDbDateTime($highBound)));
		}
	}
}