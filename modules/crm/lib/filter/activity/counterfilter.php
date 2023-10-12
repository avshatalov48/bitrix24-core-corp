<?php

namespace Bitrix\Crm\Filter\Activity;


use Bitrix\Crm\Counter\EntityCounter;
use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Entity\EntityBase;
use Bitrix\Crm\Entity\EntityManager;
use Bitrix\Crm\Filter\EntityDataProvider;
use Bitrix\Crm\Settings\CounterSettings;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\NotSupportedException;

final class CounterFilter
{
	private ExtractUsersFromFilter $extractUsers;

	private ExtractCounterTypeIdFromFilter $extractCounterTypeId;

	private string $queryApproach;

	public function __construct(string $queryApproach)
	{
		$this->queryApproach = $queryApproach;
		$this->extractUsers = new ExtractUsersFromFilter();
		$this->extractCounterTypeId = new ExtractCounterTypeIdFromFilter();
	}

	public function applyCounterFilter(int $entityTypeId, array &$filterFields, array $extras = []): void
	{
		$counterTypeId = $this->extractCounterTypeId->extract($filterFields);
		unset($filterFields['ACTIVITY_COUNTER']);

		if ($counterTypeId === null)
		{
			return;
		}

		[$counterUserIds, $isExcludeUsers] = $this->extractUserFilterParamsFromFilter($filterFields);

		try
		{
			$counter = $this->getCounter($entityTypeId, $counterTypeId, $extras);
			$this->prepareFilterFields($filterFields, $entityTypeId, $counter, $counterUserIds, $isExcludeUsers);
		}
		catch (NotSupportedException|ArgumentException $e)
		{
		}
	}

	private function getCounter(int $entityTypeId, int $counterTypeId, array $extras): EntityCounter
	{
		return EntityCounterFactory::create($entityTypeId, $counterTypeId, 0, $extras);
	}

	private function prepareFilterFields(
		array &$filterFields,
		int $entityTypeId,
		EntityCounter $counter,
		array $counterUserIds,
		bool $isExcludeUsers
	): void
	{

		if ($this->queryApproach === EntityDataProvider::QUERY_APPROACH_ORM)
		{
			$this->prepareFilterFieldsWithFactory($filterFields, $counter, $counterUserIds, $isExcludeUsers);
		}
		elseif ($this->queryApproach === EntityDataProvider::QUERY_APPROACH_BUILDER)
		{
			$entity = EntityManager::resolveByTypeID($entityTypeId);
			$this->prepareFilterFieldsWithoutFactory($entity, $filterFields, $counter, $counterUserIds, $isExcludeUsers);
		}
	}

	private function prepareFilterFieldsWithFactory(
		array &$filterFields,
		EntityCounter $counter,
		array $counterUserIds,
		bool $isExcludeUsers
	): void
	{
		$activitySubQuery = $counter->getEntityListSqlExpression(
			[
				'MASTER_ALIAS' => null,
				'MASTER_IDENTITY' => null,
				'USER_IDS' => $counterUserIds,
				'EXCLUDE_USERS' => $isExcludeUsers,
			]
		);
		$filterFields[] = ['@ID' => new SqlExpression($activitySubQuery)];
	}

	private function prepareFilterFieldsWithoutFactory(
		EntityBase $entity,
		array &$filterFields,
		EntityCounter $counter,
		array $counterUserIds,
		bool $isExcludeUsers
	): void
	{
		$filterFields += $counter->prepareEntityListFilter(
			[
				'MASTER_ALIAS' => $entity->getDbTableAlias(),
				'MASTER_IDENTITY' => 'ID',
				'USER_IDS' => $counterUserIds,
				'EXCLUDE_USERS' => $isExcludeUsers,
			]
		);
	}

	private function extractUserFilterParamsFromFilter(array &$filterFields): array
	{
		$isUseActivityResponsible = CounterSettings::getInstance()->useActivityResponsible();

		if ($isUseActivityResponsible)
		{
			$userFilterName = 'ACTIVITY_RESPONSIBLE_IDS';
		}
		else
		{
			$userFilterName = 'ASSIGNED_BY_ID';
		}

		[$counterUserIds, $isExcludeUsers] = $this->extractUsers->extract($filterFields, $userFilterName);

		// The user filter will be used together with counter, so clean it.
		unset($filterFields[$userFilterName]);
		unset($filterFields['!' . $userFilterName]);

		return [$counterUserIds, $isExcludeUsers];
	}
}
