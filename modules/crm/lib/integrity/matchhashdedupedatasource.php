<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Crm\Integrity\Entity\AutomaticDuplicateIndexTable;
use Bitrix\Main;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\Result;

abstract class MatchHashDedupeDataSource extends DedupeDataSource
{
	abstract protected function createCriterionFromMatches(array $matches): DuplicateCriterion;
	abstract protected function prepareResult(array &$map, DedupeDataSourceResult $result): void;

	public function __construct($typeID, DedupeParams $params)
	{
		parent::__construct($typeID, $params);
	}

	public function getTotalCount(): int
	{
		$result = $this->getDuplicateHashesQuery();
		if (!$result->isSuccess())
		{
			return 0;
		}

		$subQuery = $result->getData()['query'];
		$subQuery->setOrder([]);

		$query = new Main\Entity\Query($subQuery);
		$query->registerRuntimeField('', new Main\Entity\ExpressionField('CNT', 'COUNT(*)'));
		$query->addSelect('CNT');

		$fields = $query->exec()->fetch();

		return is_array($fields) && isset($fields['CNT']) ? (int)$fields['CNT'] : 0;
	}

	public function getDuplicateByMatchHash(string $matchHash): ?Duplicate
	{
		return $this
			->getListInternal(['=MATCH_HASH' => $matchHash], 0, 1)
			->getItem($matchHash)
		;
	}

	public function getList($offset, $limit): DedupeDataSourceResult
	{
		return $this->getListInternal([], $offset, $limit);
	}

	public function dropDedupeCache(): void
	{
		$this->getDedupeCache()->drop();
	}

	protected function getPermissionSql(): Result
	{
		$result = new Result();

		$permissionSql = '';

		if ($this->isPermissionCheckEnabled())
		{
			$permissionSql = $this->preparePermissionSql();
			if ($permissionSql === false)
			{
				// Access denied;
				$result->addError(new Main\Error('Access denied'));

				return $result;
			}
		}

		$result->setData(['permissionSql' => $permissionSql]);

		return $result;
	}

	protected function getDuplicateHashesOriginalQuery(array $filter = []): Result
	{
		$result = new Result();

		$typeId = (int)$this->getTypeID();
		$entityTypeId = (int)$this->getEntityTypeID();
		$scope = (string)$this->getScope();
		$userId = (int)$this->getUserID();
		$indexDateFilterValue = $this->params->getIndexDateFilterValue();

		$query = Entity\DuplicateEntityMatchHashTable::query();

		$query->setFilter($filter);

		$query->addSelect('MATCH_HASH');
		$query->addGroup('MATCH_HASH');
		$query->addOrder('MATCH_HASH');

		$query->registerRuntimeField('', new Main\Entity\ExpressionField('QTY', 'COUNT(*)'));
		$query->addSelect('QTY');
		$query->addFilter('>QTY', 1);

		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeId);
		$query->addFilter('=TYPE_ID', $typeId);

		if ($indexDateFilterValue !== '')
		{
			$query->registerRuntimeField('',
				new Main\Entity\ExpressionField('MAX_HASH_DATE_MODIFY',
					new Main\DB\SqlExpression('MAX(?#.?#)', $query->getInitAlias(), 'DATE_MODIFY')
				)
			);
			$query->addFilter('>=MAX_HASH_DATE_MODIFY', $indexDateFilterValue);
		}

		$permissionSqlResult = $this->getPermissionSql();
		if (!$permissionSqlResult->isSuccess())
		{
			$result->addErrors($permissionSqlResult->getErrors());

			return $result;
		}
		else
		{
			$permissionSql = $permissionSqlResult->getData()['permissionSql'];
			if ($permissionSql !== '')
			{
				$query->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
			}
		}

		// process only dirty items which already existed into automatic index:
		if ($this->params->limitByDirtyIndexItems())
		{
			$query->registerRuntimeField('', new Reference(
				'MATCH_HASH_DIRTY_INDEX',
				AutomaticDuplicateIndexTable::class,
				[
					'=this.MATCH_HASH' => 'ref.MATCH_HASH',
					'=this.ENTITY_TYPE_ID' => 'ref.ENTITY_TYPE_ID',
					'=this.TYPE_ID' => 'ref.TYPE_ID',
					'=ref.USER_ID' => new Main\DB\SqlExpression('?i', $userId),
					'=ref.ENTITY_TYPE_ID' => new Main\DB\SqlExpression('?i', $entityTypeId),
					'=ref.SCOPE' => new Main\DB\SqlExpression('?s', $scope),
					'=ref.IS_DIRTY' => new Main\DB\SqlExpression('?s', 'Y'),
				],
				['join_type' => \Bitrix\Main\ORM\Query\Join::TYPE_INNER]
			));
		}

		$query = DedupeDataSource::registerRuntimeFieldsByParams($query, $this->params);

		if ($scope === DuplicateIndexType::DEFAULT_SCOPE)
		{
			$query->addFilter('=SCOPE', DuplicateIndexType::DEFAULT_SCOPE);
		}
		else
		{
			$query->addFilter('@SCOPE', array(DuplicateIndexType::DEFAULT_SCOPE, $scope));
		}

		$result->setData(['query' => $query]);

		return $result;
	}

	protected function getDedupeCache(): MatchHashDedupeCache
	{
		$contextId = $this->getContextId();
		$typeId = (int)$this->getTypeID();
		$entityTypeId = (int)$this->getEntityTypeID();
		$scope = (string)$this->getScope();
		$userId = (int)$this->getUserID();
		$indexDateFilterValue = $this->params->getIndexDateFilterValue();

		return new MatchHashDedupeCache(
			new MatchHashDedupeQueryParams(
				$contextId,
				$typeId,
				$entityTypeId,
				$userId,
				$scope,
				$this->isPermissionCheckEnabled(),
				$indexDateFilterValue
			)
		);
	}

	protected function getDuplicateHashesBaseQuery(array $filter = []): Result
	{
		$isUsingDedupeCache = (
			MatchHashDedupeCache::isEnabled()
			&& $this->getContextId() !== ''
			&& ($filter === [] || (isset($filter['=MATCH_HASH']) && count($filter) === 1))
		);

		if ($isUsingDedupeCache)
		{
			$dedupeCache = $this->getDedupeCache();
			if (!$dedupeCache->isExists())
			{
				$originalQueryResult = $this->getDuplicateHashesOriginalQuery($filter);
				if (!$originalQueryResult->isSuccess())
				{
					return $originalQueryResult;
				}
				/** @var Main\ORM\Query\Query $originalQuery */
				$originalQuery = $originalQueryResult->getData()['query'];
				$originalQuery->setOrder([]);
				$createResult = $dedupeCache->create($originalQuery);
				if (!$createResult->isSuccess())
				{
					return $createResult;
				}
			}

			$result = $dedupeCache->getQuery();
			if ($result->isSuccess())
			{
				$resultData = $result->getData();
				if (is_array($resultData))
				{
					$resultData['identifyingColumn'] = 'ID';
					$result->setData($resultData);
				}

				return $result;
			}
		}

		return  $this->getDuplicateHashesOriginalQuery($filter);
	}

	protected function getDuplicateHashesQuery(array $filter = [], int $offset = 0, int $limit = 0): Result
	{
		$result = new Result();

		$baseQueryResult = $this->getDuplicateHashesBaseQuery($filter);
		if (!$baseQueryResult->isSuccess())
		{
			$result->addErrors($baseQueryResult->getErrors());

			return $result;
		}

		$resultData = $baseQueryResult->getData();

		/** @var Main\ORM\Query\Query $query */
		$query = $resultData['query'];

		if (!is_int($offset))
		{
			$offset = (int)$offset;
		}

		if (isset($resultData['identifyingColumn']))
		{
			$query->addFilter('>' . $resultData['identifyingColumn'], $offset);
			$resultData = ['isUsingOffsetByColumn' => true];
		}
		else
		{
			if ($offset > 0)
			{
				$query->setOffset($offset);
			}

			$resultData = [];
		}

		if ($limit > 0)
		{
			$query->setLimit($limit);
		}

		$resultData['query'] = $query;
		$result->setData($resultData);

		return $result;
	}

	protected function getDuplicateHashes(
		DedupeDataSourceResult $dataSourceResult,
		array $filter,
		int $offset,
		int $limit
	): Result
	{
		$result = $this->getDuplicateHashesQuery($filter, $offset, $limit);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$query = $result->getData()['query'];

		$result = new Result();

		$dbResult = $query->exec();

		$processedItemCount = 0;
		$lightHashes = [];
		$heavyHashes = [];
		while($fields = $dbResult->fetch())
		{
			$processedItemCount++;

			$quantity = (int)($fields['QTY'] ?? 0);
			$matchHash = $fields['MATCH_HASH'] ?? '';
			if ($matchHash === '' || $quantity < 2)
			{
				$dataSourceResult->addInvalidItem($matchHash);
				continue;
			}

			if ($quantity <= 100)
			{
				$lightHashes[] = $matchHash;
			}
			else
			{
				$heavyHashes[] = $matchHash;
			}
		}

		$dataSourceResult->setProcessedItemCount($processedItemCount);

		$result->setData(
			[
				'lightHashes' => $lightHashes,
				'heavyHashes' => $heavyHashes,
			]
		);

		return $result;
	}

	protected function getListInternal(array $filter, $offset, $limit): DedupeDataSourceResult
	{
		$result = new DedupeDataSourceResult();

		$typeID = $this->typeID;
		$entityTypeID = $this->getEntityTypeID();
		$scope = $this->getScope();

		$permissionSqlResult = $this->getPermissionSql();
		if (!$permissionSqlResult->isSuccess())
		{
			//Access denied;
			return $result;
		}
		else
		{
			$permissionSql = $permissionSqlResult->getData()['permissionSql'];
		}

		$duplicateHashesResult = $this->getDuplicateHashes($result, $filter, $offset, $limit);
		if (!$duplicateHashesResult->isSuccess())
		{
			return $result;
		}

		$duplicateHashesResult = $duplicateHashesResult->getData();
		$lightHashes = $duplicateHashesResult['lightHashes'] ?? [];
		$heavyHashes = $duplicateHashesResult['heavyHashes'] ?? [];

		$map = [];
		if(!empty($heavyHashes))
		{
			foreach($heavyHashes as $matchHash)
			{
				$query = new Main\Entity\Query(Entity\DuplicateEntityMatchHashTable::getEntity());
				$query->addSelect('ENTITY_ID');
				$query->addSelect('IS_PRIMARY');

				$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
				$query->addFilter('=TYPE_ID', $typeID);
				$query->addFilter('=MATCH_HASH', $matchHash);

				if ($permissionSql !== '')
				{
					$query->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
				}

				if ($scope === DuplicateIndexType::DEFAULT_SCOPE)
				{
					$query->addFilter('=SCOPE', DuplicateIndexType::DEFAULT_SCOPE);
				}
				else
				{
					$query->addFilter('@SCOPE', array(DuplicateIndexType::DEFAULT_SCOPE, $scope));
				}

				$query = DedupeDataSource::registerRuntimeFieldsByParams($query, $this->params);

				$query->setOffset(0);
				$query->setLimit(100);

				$dbResult = $query->exec();
				while($fields = $dbResult->fetch())
				{
					$entityID = isset($fields['ENTITY_ID']) ? (int)$fields['ENTITY_ID'] : 0;
					if($entityID <= 0)
					{
						continue;
					}

					if(!isset($map[$matchHash]))
					{
						$map[$matchHash] = array();
					}

					$isPrimary = isset($fields['IS_PRIMARY']) && $fields['IS_PRIMARY'] === 'Y';
					if($isPrimary)
					{
						if(!isset($map[$matchHash]['PRIMARY']))
						{
							$map[$matchHash]['PRIMARY'] = array();
						}
						$map[$matchHash]['PRIMARY'][] = $entityID;
					}
					else
					{
						if(!isset($map[$matchHash]['SECONDARY']))
						{
							$map[$matchHash]['SECONDARY'] = array();
						}
						$map[$matchHash]['SECONDARY'][] = $entityID;
					}
				}
			}
		}
		if(!empty($lightHashes))
		{
			$query = new Main\Entity\Query(Entity\DuplicateEntityMatchHashTable::getEntity());
			$query->addSelect('ENTITY_ID');
			$query->addSelect('MATCH_HASH');
			$query->addSelect('IS_PRIMARY');

			$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
			$query->addFilter('=TYPE_ID', $typeID);
			$query->addFilter('@MATCH_HASH', $lightHashes);

			if ($permissionSql !== '')
			{
				$query->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
			}

			if ($scope === DuplicateIndexType::DEFAULT_SCOPE)
			{
				$query->addFilter('=SCOPE', DuplicateIndexType::DEFAULT_SCOPE);
			}
			else
			{
				$query->addFilter('@SCOPE', array(DuplicateIndexType::DEFAULT_SCOPE, $scope));
			}

			$query = DedupeDataSource::registerRuntimeFieldsByParams($query, $this->params);

			$dbResult = $query->exec();
			while($fields = $dbResult->fetch())
			{
				$entityID = isset($fields['ENTITY_ID']) ? (int)$fields['ENTITY_ID'] : 0;
				if($entityID <= 0)
				{
					continue;
				}

				$matchHash = $fields['MATCH_HASH'] ?? '';
				if($matchHash === '')
				{
					continue;
				}

				if(!isset($map[$matchHash]))
				{
					$map[$matchHash] = array();
				}

				$isPrimary = isset($fields['IS_PRIMARY']) && $fields['IS_PRIMARY'] === 'Y';
				if($isPrimary)
				{
					if(!isset($map[$matchHash]['PRIMARY']))
					{
						$map[$matchHash]['PRIMARY'] = array();
					}
					$map[$matchHash]['PRIMARY'][] = $entityID;
				}
				else
				{
					if(!isset($map[$matchHash]['SECONDARY']))
					{
						$map[$matchHash]['SECONDARY'] = array();
					}
					$map[$matchHash]['SECONDARY'][] = $entityID;
				}
			}
		}

		$this->prepareResult($map, $result);

		if ($this->params->limitByAssignedUser())
		{
			foreach ($result->getItems() as $duplicate)
			{
				/** @var $duplicate Duplicate */
				$criterion = $duplicate->getCriterion();
				/** @var $criterion DuplicateCriterion */
				if ($criterion)
				{
					$criterion->setLimitByAssignedUser(true);
				}
				$entities = $duplicate->getEntities();
				foreach ($entities as $entity)
				{
					/** @var $entity DuplicateEntity */
					if ($entity->getCriterion())
					{
						$entity->getCriterion()->setLimitByAssignedUser(true);
					}
				}
			}
		}

		return $result;
	}
}
