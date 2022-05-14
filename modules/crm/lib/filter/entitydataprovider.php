<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Crm\CompanyAddress;
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

	protected function applyCounterFilter(int $entityTypeId, array &$filterFields): void
	{
		if (!isset($filterFields['ACTIVITY_COUNTER']))
		{
			return;
		}

		if(is_array($filterFields['ACTIVITY_COUNTER']))
		{
			$counterTypeId = \Bitrix\Crm\Counter\EntityCounterType::joinType(
				array_filter($filterFields['ACTIVITY_COUNTER'], 'is_numeric')
			);
		}
		else
		{
			$counterTypeId = (int)$filterFields['ACTIVITY_COUNTER'];
		}
		unset($filterFields['ACTIVITY_COUNTER']);

		if($counterTypeId > 0)
		{
			$counterUserIds = [];
			if(isset($filterFields['ASSIGNED_BY_ID']))
			{
				if(is_array($filterFields['ASSIGNED_BY_ID']))
				{
					$counterUserIds = array_filter($filterFields['ASSIGNED_BY_ID'], 'is_numeric');
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
					$this->getCounterExtras()
				);

				$entity = \Bitrix\Crm\Entity\EntityManager::resolveByTypeID($entityTypeId);
				if ($entity)
				{
					$filterFields += $counter->prepareEntityListFilter(
						[
							'MASTER_ALIAS' => $entity->getDbTableAlias(),
							'MASTER_IDENTITY' => 'ID',
							'USER_IDS' => $counterUserIds
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
	}

	protected function applySettingsDependantFilter(array &$filterFields): void
	{
	}

	protected function getCounterExtras(): array
	{
		return [];
	}
}
