<?php

namespace Bitrix\Crm\Filter\Activity;

use Bitrix\Crm\Counter\EntityCounterType;

final class PrepareActivityFilter
{
	private ?int $currentUserId;

	public function __construct(?int $currentUserId)
	{
		$this->currentUserId = $currentUserId;
	}

	public function prepare(array $filterFields): PrepareResult
	{
		if (!isset($filterFields['ACTIVITY_COUNTER']))
		{
			return $this->onlyAssignWithoutCounter($filterFields);
		}

		if(is_array($filterFields['ACTIVITY_COUNTER']))
		{
			$counterTypeId = $this->joinActivityCounterIds($filterFields['ACTIVITY_COUNTER']);
			if ($counterTypeId <= 0)
			{
				return new PrepareResult($filterFields);
			}
		}
		else
		{
			$counterTypeId = (int)$filterFields['ACTIVITY_COUNTER'];
			if (!EntityCounterType::isDefined($counterTypeId))
			{
				return new PrepareResult($filterFields);
			}
		}
		unset($filterFields['ACTIVITY_COUNTER']);

		$counterUserIds = [];
		$excludeUsers = false;
		if(isset($filterFields['ASSIGNED_BY_ID']))
		{
			if (is_array($filterFields['ASSIGNED_BY_ID']))
			{
				if ($this->isAllUsers($filterFields['ASSIGNED_BY_ID']))
				{
					unset($filterFields['ASSIGNED_BY_ID']);
				}
				elseif ($this->isOtherUsers($filterFields['ASSIGNED_BY_ID']))
				{
					$counterUserIds[] = $this->currentUserId;
					$excludeUsers = true;
					unset($filterFields['ASSIGNED_BY_ID']);
				}
				else
				{
					$counterUserIds = array_filter($filterFields['ASSIGNED_BY_ID'], 'is_numeric');
				}
			}
			elseif($filterFields['ASSIGNED_BY_ID'] > 0)
			{
				$counterUserIds[] = $filterFields['ASSIGNED_BY_ID'];
			}
		}
		return new PrepareResult(
			$filterFields,
			$counterUserIds,
			$excludeUsers,
			$counterTypeId,
			true
		);
	}

	private function isAllUsers(array $assignedFilter): bool
	{
		return (
			in_array('all-users', $assignedFilter, true)
			|| (
				in_array('other-users', $assignedFilter, true)
				&& $this->isCurrentUserInFilter($assignedFilter)
			)
		);
	}

	private function isOtherUsers(array $assignedFilter): bool
	{
		return (
			in_array('other-users', $assignedFilter, true)
			&& !$this->isCurrentUserInFilter($assignedFilter)
		);
	}

	private function isCurrentUserInFilter(array $assignedFilter): bool
	{
		return (
			$this->currentUserId > 0
			&& in_array($this->currentUserId, $assignedFilter)
		);
	}

	private function onlyAssignWithoutCounter(array $filterFields): PrepareResult
	{
		if (isset($filterFields['ASSIGNED_BY_ID']) && is_array($filterFields['ASSIGNED_BY_ID']))
		{
			if ($this->isAllUsers($filterFields['ASSIGNED_BY_ID']))
			{
				unset($filterFields['ASSIGNED_BY_ID']);
			}
			elseif ($this->isOtherUsers($filterFields['ASSIGNED_BY_ID']))
			{
				$filterFields['!ASSIGNED_BY_ID'] = $this->currentUserId;
				unset($filterFields['ASSIGNED_BY_ID']);
			}
		}

		return new PrepareResult($filterFields);
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