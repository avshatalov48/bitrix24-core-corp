<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Search\SearchContentBuilderFactory;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;

abstract class EntityDataProvider extends Main\Filter\EntityDataProvider
{
	public function prepareFilterValue(array $rawFilterValue): array
	{
		$filterValue = parent::prepareFilterValue($rawFilterValue);

		$factory = Container::getInstance()->getFactory($this->getSettings()->getEntityTypeID());
		if (!$factory)
		{
			return $filterValue;
		}

		$this->applySearchString($factory->getEntityTypeId(), $filterValue);
		$this->applyParentFieldFilter($filterValue);

		if ($factory->isMultiFieldsEnabled())
		{
			$this->applyMultifieldFilter($filterValue);
		}

		if ($factory->isCountersEnabled())
		{
			$this->applyCounterFilter($factory->getEntityTypeId(), $filterValue);
		}

		$this->applySettingsDependantFilter($filterValue);

		return $filterValue;
	}

	protected function applyParentFieldFilter(array &$filterValue): void
	{
		foreach ($filterValue as $k=>$v)
		{
			if (\Bitrix\Crm\Service\ParentFieldManager::isParentFieldName($k))
			{
				$filterValue[$k] = \Bitrix\Crm\Service\ParentFieldManager::transformEncodedFilterValueIntoInteger($k, $v);
			}
		}
	}

	protected function applySearchString(int $entityTypeId, array &$filterValue): void
	{
		try
		{
			SearchContentBuilderFactory::create($entityTypeId)->convertEntityFilterValues($filterValue);
		}
		catch (\Bitrix\Main\NotSupportedException $e)
		{
			//  just do nothing if $entityTypeId is not supported by SearchContentBuilderFactory
		}
	}

	protected function applyMultifieldFilter(array &$filterValue): void
	{
		\CCrmEntityHelper::PrepareMultiFieldFilter($filterValue, [], '=%', false);
	}

	public function applyCounterFilter(int $entityTypeId, array &$filterFields, array $extras = []): void
	{
		if (!isset($filterFields['ACTIVITY_COUNTER']))
		{
			if (isset($filterFields['ASSIGNED_BY_ID']) && is_array($filterFields['ASSIGNED_BY_ID']))
			{
				if ($this->isAllUsers($filterFields['ASSIGNED_BY_ID']))
				{
					unset($filterFields['ASSIGNED_BY_ID']);
				}
				elseif ($this->isOtherUsers($filterFields['ASSIGNED_BY_ID']))
				{
					$filterFields['!ASSIGNED_BY_ID'] = Container::getInstance()->getContext()->getUserId();
					unset($filterFields['ASSIGNED_BY_ID']);
				}
			}

			return;
		}

		if(is_array($filterFields['ACTIVITY_COUNTER']))
		{
			$counterTypeId = \Bitrix\Crm\Counter\EntityCounterType::joinType(
				array_filter($filterFields['ACTIVITY_COUNTER'], function ($value) {
					return is_numeric($value) && EntityCounterType::isDefined($value);
				})
			);
			if ($counterTypeId <= 0)
			{
				return;
			}
		}
		else
		{
			$counterTypeId = (int)$filterFields['ACTIVITY_COUNTER'];
			if (!EntityCounterType::isDefined($counterTypeId))
			{
				return;
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
					$counterUserIds = [];
					unset($filterFields['ASSIGNED_BY_ID']);
				}
				elseif ($this->isOtherUsers($filterFields['ASSIGNED_BY_ID']))
				{
					$counterUserIds[] = Container::getInstance()->getContext()->getUserId();
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

		try
		{
			$counter = \Bitrix\Crm\Counter\EntityCounterFactory::create(
				$entityTypeId,
				$counterTypeId,
				0,
				array_merge($extras, $this->getCounterExtras()),
			);

			$entity = \Bitrix\Crm\Entity\EntityManager::resolveByTypeID($entityTypeId);
			if ($entity)
			{
				$filterFields += $counter->prepareEntityListFilter(
					[
						'MASTER_ALIAS' => $entity->getDbTableAlias(),
						'MASTER_IDENTITY' => 'ID',
						'USER_IDS' => $counterUserIds,
						'EXCLUDE_USERS' => $excludeUsers,
					]
				);
				unset($filterFields['ASSIGNED_BY_ID']);
			}
		}
		catch(\Bitrix\Main\NotSupportedException $e)
		{
		}
		catch(\Bitrix\Main\ArgumentException $e)
		{
		}
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
		$currentUserId = Container::getInstance()->getContext()->getUserId();

		return (
			$currentUserId > 0
			&& in_array($currentUserId, $assignedFilter, false)
		);
	}

	protected function applySettingsDependantFilter(array &$filterFields): void
	{
	}

	protected function getCounterExtras(): array
	{
		return [];
	}
}
