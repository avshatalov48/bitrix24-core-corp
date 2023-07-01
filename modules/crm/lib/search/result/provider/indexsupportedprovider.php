<?php

namespace Bitrix\Crm\Search\Result\Provider;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Crm\Search\Result;
use Bitrix\Main\Search\Content;
use Bitrix\Crm\Search\SearchEnvironment;
use Bitrix\Main\ORM\Query\Filter\Helper;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\RequisiteTable;
use Bitrix\Main\Entity\ReferenceField;

abstract class IndexSupportedProvider extends \Bitrix\Crm\Search\Result\Provider
{
	private $permissionSql = null;
	public function getSearchResult(string $searchQuery): Result
	{
		$result = new Result();

		$searchQuery = trim($searchQuery);
		if ($searchQuery === '')
		{
			return $result;
		}

		if ($this->useDenominationSearch)
		{
			return $this->searchByDenomination($searchQuery);
		}

		$preparedSearchQuery = $this->prepareSearchQuery($searchQuery);

		$priority = 0;

		// search in short index
		$limit = $this->getRemainingLimit($result);
		if ($this->isShortIndexSupported() && $limit > 0)
		{
			$ids = $this->searchInShortIndex($preparedSearchQuery, $limit);
			if (!empty($ids))
			{
				$result->addIdsByPriority($priority, $ids);
				$priority++;
			}
		}

		// search in full index
		$limit = $this->getRemainingLimit($result);
		if ($this->isFullIndexSupported() && $limit > 0)
		{
			$ids = $this->searchInFullIndex($preparedSearchQuery, $limit, $result->getIds());
			if (!empty($ids))
			{
				$result->addIdsByPriority($priority++, $ids);
			}

			// add extra search results for raw numbers (not interpreted as phone):
			$limit = $this->getRemainingLimit($result);
			if (
				Content::isIntegerToken($searchQuery)
				&& $searchQuery !== $preparedSearchQuery
				&& Content::canUseFulltextSearch($searchQuery, Content::TYPE_STRING)
				&& $limit > 0
			)
			{
				$ids = $this->searchInFullIndex($searchQuery, $limit, $result->getIds());
				if (!empty($ids))
				{
					$result->addIdsByPriority($priority++, $ids);
				}
			}
		}

		// search in requisites fields
		$limit = $this->getRemainingLimit($result);
		if (
			$this->areRequisitesSupported()
			&& Content::isIntegerToken($searchQuery) // only search in requisite fields by numbers supported
			&& $limit > 0
		)
		{
			$ids = $this->searchInRequisites($searchQuery, $limit, $result->getIds());
			if (!empty($ids))
			{
				$result->addIdsByPriority($priority++, $ids);
			}
		}

		return $result;
	}

	protected function getRemainingLimit(Result $result): int
	{
		return max(
			0,
			$this->limit - count($result->getIds())
		);
	}

	protected function prepareSearchQuery(string $searchQuery): string
	{
		$result = SearchEnvironment::prepareSearchContent($searchQuery);

		$result = Content::isIntegerToken($result)
			? Content::prepareIntegerToken($result)
			: Content::prepareStringToken($result);

		return $result;
	}

	protected function searchInShortIndex(string $searchQuery, int $limit = 0, array $excludedIds = []): array
	{
		$query = $this->getIndexTableQuery();
		$columnName = $this->getShortIndexColumnName();
		if (!empty($this->additionalFilter))
		{
			$referenceFilter = (new ConditionTree())
				->whereColumn('this.' . $columnName, 'ref.ID')
			;
			$this->addToReferenceFilter($referenceFilter, $this->additionalFilter);
			$query->registerRuntimeField('',
				new \Bitrix\Main\Entity\ReferenceField('ENTITY',
					$this->getEntityTableQuery()->getEntity(),
					$referenceFilter,
					['join_type' => 'INNER'],
				)
			);
		}
		if (!empty($excludedIds))
		{
			$query->whereNotIn($columnName, $excludedIds);
		}
		if ($limit > 0)
		{
			$query->setLimit($limit);
		}

		return $this->fetchColumnValuesFromIndex(
			$query,
			$columnName,
			$searchQuery
		);
	}

	protected function searchInFullIndex(string $searchQuery, int $limit = 0, array $excludedIds = []): array
	{
		$query = $this->getEntityTableQuery();
		$columnName = 'ID';

		if (!empty($this->additionalFilter))
		{
			$query->setFilter($this->additionalFilter);
		}
		if (!empty($excludedIds))
		{
			$query->whereNotIn($columnName, $excludedIds);
		}
		if ($limit > 0)
		{
			$query->setLimit($limit);
		}

		return $this->fetchColumnValuesFromIndex(
			$query,
			$columnName,
			$searchQuery
		);
	}

	protected function searchInRequisites(string $searchQuery, int $limit = 0, array $excludedIds = []): array
	{
		$result = [];

		$minSearchQueryLength = 5;
		if (mb_strlen($searchQuery) < $minSearchQueryLength)
		{
			return $result;
		}

		$entityRequisiteMap = EntityRequisite::getDuplicateCriterionFieldsMap();
		$countryId = EntityPreset::getCurrentCountryId();

		$requisiteFields = $entityRequisiteMap[$countryId] ?? [];

		if (empty($requisiteFields))
		{
			return $result;
		}

		$permissionSql = $this->getPermissionSql();
		if ($permissionSql === false) // access denied
		{
			return $result;
		}

		$query = RequisiteTable::query();
		$query->setSelect(['ENTITY_ID']);
		$query->setGroup('ENTITY_ID');
		$query->setLimit($limit);
		$query->where('ENTITY_TYPE_ID', $this->getEntityTypeId());
		if (!empty($excludedIds))
		{
			$query->whereNotIn('ENTITY_ID', $excludedIds);
		}

		$requisiteFieldsFilter = Query::filter();
		$requisiteFieldsFilter->logic('or');
		foreach ($requisiteFields as $field)
		{
			$requisiteFieldsFilter->whereLike(
				$field,
				$searchQuery . '%'
			);
		}
		$query->where($requisiteFieldsFilter);

		$query->registerRuntimeField(
			'',
			new ReferenceField(
				'ENTITY',
				$this->getEntityTableQuery()->getEntity(),
				[
					'=this.ENTITY_ID' => 'ref.ID',
				],
				['join_type' => 'INNER']
			)
		);
		if ($permissionSql !== '')
		{
			$query->addFilter('@ENTITY.ID', new SqlExpression($permissionSql));
		}

		$items = $query->exec();
		while ($item = $items->fetch())
		{
			$result[] = $item['ENTITY_ID'];
		}

		return $result;
	}

	protected function fetchColumnValuesFromIndex(
		Query $query,
		string $columnName,
		string $searchContent
	): array
	{
		$result = [];

		if (Content::canUseFulltextSearch($searchContent, Content::TYPE_MIXED))
		{
			$searchContent = Helper::matchAgainstWildcard($searchContent);

			$permissionSql = $this->getPermissionSql();
			if ($permissionSql === false)
			{
				return [];
			}

			$query
				->setSelect([$columnName])
				->whereMatch('SEARCH_CONTENT', $searchContent)
			;

			if ($permissionSql !== '')
			{
				$query->addFilter('@' . $columnName, new SqlExpression($permissionSql));
			}

			$items = $query->exec();
			while ($item = $items->fetch())
			{
				$result[] = $item[$columnName];
			}
		}

		return $result;
	}

	protected function getPermissionSql()
	{
		if (!$this->checkPermissions)
		{
			return ''; // empty string means no permissions check
		}

		if ($this->permissionSql === null)
		{
			$this->permissionSql = '';

			if (!\CCrmPerms::IsAdmin($this->userId))
			{
				$this->permissionSql = \CCrmPerms::BuildSqlForEntitySet(
					$this->getPermissionEntityTypes(),
					'',
					'READ',
					[
						'RAW_QUERY' => true,
						'PERMS' => \CCrmPerms::GetUserPermissions($this->userId),
					]
				);
			}
		}

		return $this->permissionSql;
	}

	protected function isShortIndexSupported(): bool
	{
		return true;
	}

	protected function isFullIndexSupported(): bool
	{
		return true;
	}

	abstract protected function getEntityTypeId(): int;

	abstract protected function getPermissionEntityTypes(): array;

	abstract protected function getShortIndexColumnName(): string;

	abstract protected function areRequisitesSupported(): bool;

	abstract protected function getIndexTableQuery(): Query;

	abstract protected function getEntityTableQuery(): Query;

	abstract protected function searchByDenomination(string $searchQuery): Result;

	protected function addToReferenceFilter(ConditionTree $referenceFilter, array $entityFilter): void
	{
		$sqlWhere = new \CSQLWhere();
		foreach ($entityFilter as $filterKey => $filterValue)
		{
			$operationData = $sqlWhere->makeOperation($filterKey);
			$operation = \CSQLWhere::getOperationByCode($operationData['OPERATION']);
			$field = $operationData['FIELD'];
			if ($operation === '@')
			{
				$referenceFilter->whereIn( 'ref.' . $field, $filterValue);
			}
			else
			{
				$referenceFilter->where('ref.' . $field, $operation, new SqlExpression('?', $filterValue));
			}
		}
	}

	protected function getPermissionEntityTypesByAffectedCategories(): array
	{
		$categories =
			is_array($this->affectedCategories) && !empty($this->affectedCategories)
				? $this->affectedCategories
				: [0]
		;
		$permissionHelper = new PermissionEntityTypeHelper($this->getEntityTypeId());
		$result = [];
		foreach ($categories as $categoryId)
		{
			$result[] = $permissionHelper->getPermissionEntityTypeForCategory($categoryId);
		}

		return $result;
	}
}
