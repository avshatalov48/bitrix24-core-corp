<?php

namespace Bitrix\Crm\Filter\Activity;


use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Entity\EntityManager;
use Bitrix\Crm\Filter\EntityDataProvider;
use Bitrix\Main\DB\SqlExpression;

final class FilterByActivityResponsible
{

	private string $queryApproach;

	private ExtractUsersFromFilter $extractUsers;

	/**
	 * @var $queryApproach string one of EntityDataProvider::QUERY_APPROACH_ORM | EntityDataProvider::QUERY_APPROACH_BUILDER
	 *		It defines how to inject sql sub-query to the main query.
	 */
	public function __construct(string $queryApproach)
	{
		$this->queryApproach = $queryApproach;
		$this->extractUsers = new ExtractUsersFromFilter();
	}

	public function applyFilter(array &$filterFields, int $entityTypeId): void
	{
		if (
			!isset($filterFields['ACTIVITY_RESPONSIBLE_IDS']) &&
			!isset($filterFields['!ACTIVITY_RESPONSIBLE_IDS'])
		)
		{
			return;
		}

		[$userIds, $isExclude] = $this->extractUsers->extract($filterFields, 'ACTIVITY_RESPONSIBLE_IDS');

		unset($filterFields['ACTIVITY_RESPONSIBLE_IDS']);
		unset($filterFields['!ACTIVITY_RESPONSIBLE_IDS']);

		if (empty($userIds))
		{
			$userIds[] = 0;
		}

		$subQuery = $this->buildSubQuery($userIds, $isExclude, $entityTypeId);

		$filterFields = $this->applySubQueryToFilter($subQuery, $filterFields, $entityTypeId);
	}

	private function buildSubQuery(array $userIds, bool $isExclude, int $entityTypeId): string
	{
		$query = EntityUncompletedActivityTable::query()
			->setSelect(['ENTITY_ID'])
			->where('ENTITY_TYPE_ID', $entityTypeId);

		if ($isExclude)
		{
			$userIds[] = 0;
			$query->whereNotIn('RESPONSIBLE_ID', $userIds);
		}
		else
		{
			$query->whereIn('RESPONSIBLE_ID', $userIds);
		}

		return $query->getQuery();
	}

	private function applySubQueryToFilter(string $subQuery, array $filterFields, int $entityTypeId): array
	{
		if ($this->queryApproach === EntityDataProvider::QUERY_APPROACH_ORM)
		{
			$filterFields[] = ['@ID' => new SqlExpression($subQuery)];
		}
		elseif ($this->queryApproach === EntityDataProvider::QUERY_APPROACH_BUILDER)
		{
			$entity = EntityManager::resolveByTypeID($entityTypeId);
			$masterAlias = $entity->getDbTableAlias();
			$filterFields += ['__CONDITIONS' => [['SQL' => "{$masterAlias}.ID IN ({$subQuery})"]]];
		}
		return $filterFields;
	}

}