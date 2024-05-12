<?php

namespace Bitrix\Crm\Filter\Activity;


use Bitrix\Crm\Counter\EntityCounter;
use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Entity\EntityBase;
use Bitrix\Crm\Entity\EntityManager;
use Bitrix\Crm\Filter\EntityDataProvider;
use Bitrix\Crm\Filter\Filter;
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
			$this->prepareFilterFieldsWithFactory(
				$filterFields,
				$counter,
				$counterUserIds,
				$isExcludeUsers,
				$entityTypeId
			);
		}
		elseif ($this->queryApproach === EntityDataProvider::QUERY_APPROACH_BUILDER)
		{
			$entity = EntityManager::resolveByTypeID($entityTypeId);
			if ($entity)
			{
				$this->prepareFilterFieldsWithoutFactory($entity, $filterFields, $counter, $counterUserIds, $isExcludeUsers);
			}
		}
	}

	private function prepareFilterFieldsWithFactory(
		array &$filterFields,
		EntityCounter $counter,
		array $counterUserIds,
		bool $isExcludeUsers,
		int $entityTypeId
	): void
	{
		$activitySubQuery = $counter->getEntityListSqlExpression(
			[
				'MASTER_ALIAS' => null,
				'MASTER_IDENTITY' => null,
				'USER_IDS' => $counterUserIds,
				'EXCLUDE_USERS' => $isExcludeUsers,
				'STAGE_SEMANTIC_ID' => $this->extractStageSemanticFromFilter($filterFields, $entityTypeId),
			]
		);
		$filterFields[] = ['@ID' => new SqlExpression($activitySubQuery)];
	}

	private function extractStageSemanticFromFilter(array $filter, int $entityTypeId): array|string|null
	{
		$stageField = $this->getStageField($entityTypeId);
		if (array_key_exists($stageField, $filter))
		{
			return $filter[$stageField];
		}

		// try to search stage semantic filter after conversion by Bitrix\Crm\Filter\Filter::applyStageSemanticFilter
		return Filter::extractStageSemanticFilter($filter);
	}

	private function prepareFilterFieldsWithoutFactory(
		EntityBase $entity,
		array &$filterFields,
		EntityCounter $counter,
		array $counterUserIds,
		bool $isExcludeUsers
	): void
	{
		$stageField = $this->getStageField($entity->getEntityTypeID());
		$filterFields += $counter->prepareEntityListFilter(
			[
				'MASTER_ALIAS' => $entity->getDbTableAlias(),
				'MASTER_IDENTITY' => 'ID',
				'USER_IDS' => $counterUserIds,
				'EXCLUDE_USERS' => $isExcludeUsers,
				'STAGE_SEMANTIC_ID' => $filterFields[$stageField] ?? null,
			]
		);
	}

	private function getStageField(int $entityId): ?string
	{
		return match ($entityId) {
			\CCrmOwnerType::Lead => 'STATUS_SEMANTIC_ID',
			\CCrmOwnerType::Quote => '=STATUS_ID',
			default => 'STAGE_SEMANTIC_ID',
		};
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
