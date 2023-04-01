<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Counter\EntityCounter;
use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Entity\EntityBase;
use Bitrix\Crm\Entity\EntityManager;
use Bitrix\Crm\Filter\Activity\PrepareActivityFilter;
use Bitrix\Crm\Filter\Activity\PrepareResult;
use Bitrix\Crm\Search\SearchContentBuilderFactory;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;

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
		/** @var PrepareActivityFilter $prepareFilter */
		$prepareFilter = ServiceLocator::getInstance()->get('crm.lib.filter.activity.prepareactivityfilter');

		$prepareResult = $prepareFilter->prepare($filterFields);
		$filterFields = $prepareResult->filter();

		if (!$prepareResult->willApplyCounterFilter())
		{
			return;
		}

		try
		{
			$counter = $this->getCounter($entityTypeId, $prepareResult->counterTypeId(), $extras);
			$this->prepareFilterFields($filterFields, $entityTypeId, $counter, $prepareResult);
		}
		catch(\Bitrix\Main\NotSupportedException $e)
		{
		}
		catch(\Bitrix\Main\ArgumentException $e)
		{
		}
	}

	protected function getCounter(int $entityTypeId, int $counterTypeId, array $extras): EntityCounter
	{
		$extras = array_merge($extras, $this->getCounterExtras());

		return EntityCounterFactory::create($entityTypeId, $counterTypeId, 0, $extras);
	}

	protected function prepareFilterFields(
		array &$filterFields,
		int $entityTypeId,
		EntityCounter $counter,
		PrepareResult $prepareResult
	): void
	{
		if ($this instanceof ItemDataProvider)
		{
			$this->prepareFilterFieldsWithFactory($filterFields, $counter, $prepareResult);
			return;
		}

		$entity = EntityManager::resolveByTypeID($entityTypeId);
		if ($entity)
		{
			if ($this instanceof FactoryOptionable && $this->isForceUseFactory())
			{
				$this->prepareFilterFieldsWithFactory($filterFields, $counter, $prepareResult);
				return;
			}

			$this->prepareFilterFieldsWithoutFactory($entity, $filterFields, $counter, $prepareResult);
		}
	}

	protected function prepareFilterFieldsWithFactory(
		array &$filterFields,
		EntityCounter $counter,
		PrepareResult $prepareResult
	): void
	{
		$activitySubQuery = $counter->getEntityListSqlExpression(
			[
				'MASTER_ALIAS' => null,
				'MASTER_IDENTITY' => null,
				'USER_IDS' => $prepareResult->counterUserIds(),
				'EXCLUDE_USERS' => $prepareResult->isExcludeUsers(),
			]
		);
		$filterFields[] = ['@ID' => new \Bitrix\Main\DB\SqlExpression($activitySubQuery)];
		unset($filterFields['ASSIGNED_BY_ID']);
	}

	protected function prepareFilterFieldsWithoutFactory(
		EntityBase $entity,
		array &$filterFields,
		EntityCounter $counter,
		PrepareResult $prepareResult
	): void
	{
		$filterFields += $counter->prepareEntityListFilter(
			[
				'MASTER_ALIAS' => $entity->getDbTableAlias(),
				'MASTER_IDENTITY' => 'ID',
				'USER_IDS' => $prepareResult->counterUserIds(),
				'EXCLUDE_USERS' => $prepareResult->isExcludeUsers(),
			]
		);
		unset($filterFields['ASSIGNED_BY_ID']);
	}

	protected function applySettingsDependantFilter(array &$filterFields): void
	{
	}

	protected function getCounterExtras(): array
	{
		return [];
	}
}
