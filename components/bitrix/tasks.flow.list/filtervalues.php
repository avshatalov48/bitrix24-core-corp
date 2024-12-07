<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Tasks\Flow\Search;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Tasks\Internals\Counter;

class FilterValues
{
	private int $userId;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	public function prepareFilter(array $filterValues): ConditionTree
	{
		$filter = new ConditionTree();

		if (isset($filterValues['GROUP_ID']))
		{
			$filter->whereIn('GROUP_ID', $filterValues['GROUP_ID']);
		}

		if (isset($filterValues['CREATOR_ID'], $filterValues['OWNER_ID']))
		{
			$subFilter = Query::filter()
				->logic('or')
				->whereIn('CREATOR_ID', $filterValues['CREATOR_ID'])
				->whereIn('OWNER_ID', $filterValues['OWNER_ID']);

			$filter->where($subFilter);
		}
		elseif (isset($filterValues['CREATOR_ID']))
		{
			$filter->whereIn('CREATOR_ID', $filterValues['CREATOR_ID']);
		}
		elseif (isset($filterValues['OWNER_ID']))
		{
			$filter->whereIn('OWNER_ID', $filterValues['OWNER_ID']);
		}

		if (isset($filterValues['EFFICIENCY_numsel']))
		{
			switch ($filterValues['EFFICIENCY_numsel'])
			{
				case 'more':
					$from = (int)($filterValues['EFFICIENCY_from'] ?? 0);
					$filter->where('EFFICIENCY', '>', $from);
					break;
				case 'range':
					$from = (int)($filterValues['EFFICIENCY_from'] ?? 0);
					$to = (int)($filterValues['EFFICIENCY_to'] ?? 0);
					$filter->where('EFFICIENCY', '<=', $to);
					$filter->where('EFFICIENCY', '>=', $from);
					break;
				case 'less':
					$to = (int)($filterValues['EFFICIENCY_to'] ?? 0);
					$filter->where('EFFICIENCY', '<', $to);
					break;
				case 'exact':
					$from = (int)($filterValues['EFFICIENCY_from'] ?? 0);
					$filter->where('EFFICIENCY', '=', $from);
					break;
			}
		}

		if (isset($filterValues['ACTIVE']))
		{
			$filter->where('ACTIVE', $filterValues['ACTIVE'] === 'Y' ? 1 : 0);
		}

		if (isset($filterValues['ID_numsel']))
		{
			switch ($filterValues['ID_numsel'])
			{
				case 'more':
					$from = (int)($filterValues['ID_from'] ?? 0);
					$filter->where('ID', '>=', $from);
					break;
				case 'range':
					$from = (int)($filterValues['ID_from'] ?? 0);
					$to = (int)($filterValues['ID_to'] ?? 0);
					$filter->where('ID', '<=', $to);
					$filter->where('ID', '>=', $from);
					break;
				case 'less':
					$to = (int)($filterValues['ID_to'] ?? 0);
					$filter->where('ID', '<=', $to);
					break;
				case 'exact':
					$from = (int)($filterValues['ID_from'] ?? 0);
					$filter->where('ID', '=', $from);
					break;
			}
		}

		if (!empty($filterValues['PROBLEM']))
		{
			$ids = [];

			$flowRawCounters = Counter::getInstance($this->userId)
				->getRawCounters(Counter\CounterDictionary::META_PROP_FLOW)
			;

			$types = match((int)$filterValues['PROBLEM'])
			{
				Counter\Type::TYPE_EXPIRED => Counter\CounterDictionary::MAP_FLOW_TOTAL[Counter\CounterDictionary::COUNTER_FLOW_TOTAL_EXPIRED],
				Counter\Type::TYPE_NEW_COMMENTS => Counter\CounterDictionary::MAP_FLOW_TOTAL[Counter\CounterDictionary::COUNTER_FLOW_TOTAL_COMMENTS],
				default => 'none',
			};

			$counters = [];

			foreach ($types as $type)
			{
				$typeCounters = $flowRawCounters[$type] ?? [];
				foreach ($typeCounters as $flowId => $value)
				{
					$newValue = isset($counters[$flowId]) ? $counters[$flowId] + $value : $value;
					$counters[$flowId] = $newValue;
				}
			}

			foreach ($counters as $flowId => $counter)
			{
				if ($flowId)
				{
					$ids[] = (int)$flowId;
				}
			}

			if ([] !== $ids)
			{
				$filter->whereIn('ID', $ids);
			}
			else
			{
				return $filter->where('ID', 0);
			}
		}

		if (!empty($filterValues['FIND']))
		{
			$sqlExpression = (new Search\FullTextSearch())->find($filterValues['FIND']);

			if ($sqlExpression)
			{
				$filter->whereIn('ID', $sqlExpression);
			}
		}

		return $filter;
	}
}
