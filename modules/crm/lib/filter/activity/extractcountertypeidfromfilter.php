<?php

namespace Bitrix\Crm\Filter\Activity;


use Bitrix\Crm\Counter\EntityCounterType;

final class ExtractCounterTypeIdFromFilter
{

	public function extract(array $filterFields): ?int
	{
		if (!isset($filterFields['ACTIVITY_COUNTER']))
		{
			return null;
		}

		if(is_array($filterFields['ACTIVITY_COUNTER']))
		{
			$counterTypeId = $this->joinActivityCounterIds($filterFields['ACTIVITY_COUNTER']);
			if ($counterTypeId <= 0)
			{
				return null;
			}
		}
		else
		{
			$counterTypeId = (int)$filterFields['ACTIVITY_COUNTER'];
			if (!EntityCounterType::isDefined($counterTypeId))
			{
				return null;
			}
		}

		return $counterTypeId;
	}

	private function joinActivityCounterIds(array $codes): int
	{
		return EntityCounterType::joinType(
			array_filter($codes, function ($value) {
				return is_numeric($value) && EntityCounterType::isDefined($value);
			})
		);
	}
}