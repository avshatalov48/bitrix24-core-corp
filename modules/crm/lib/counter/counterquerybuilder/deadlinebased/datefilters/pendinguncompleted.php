<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

final class PendingUncompleted implements DateFilter
{

	private QueryParts\DeadlineBounds $qpDeadlineBounds;

	public function __construct()
	{
		$this->qpDeadlineBounds = new QueryParts\DeadlineBounds();
	}

	public function applyFilter(ConditionTree $ct, QueryParams $params): void
	{
		$lowBound = $this->qpDeadlineBounds->getLowBound($params);
		$highBound = $this->qpDeadlineBounds->getHighBound($params);

		$sqlHelper = Application::getConnection()->getSqlHelper();

		if ($lowBound)
		{
			$ct->where('ref.MIN_DEADLINE', '>=', new SqlExpression($sqlHelper->convertToDbDateTime($lowBound)));
		}
		if ($highBound)
		{
			$ct->where('ref.MIN_DEADLINE', '<=', new SqlExpression($sqlHelper->convertToDbDateTime($highBound)));
		}
	}
}