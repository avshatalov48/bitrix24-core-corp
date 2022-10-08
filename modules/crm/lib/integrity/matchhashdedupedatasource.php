<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Crm\Integrity\Entity\AutomaticDuplicateIndexTable;
use Bitrix\Main;
use Bitrix\Main\ORM\Fields\Relations\Reference;

abstract class MatchHashDedupeDataSource extends DedupeDataSource
{
	public function __construct($typeID, DedupeParams $params)
	{
		parent::__construct($typeID, $params);
	}
	/**
	 * @return DuplicateCriterion
	 */
	abstract protected function createCriterionFromMatches(array $matches);
	abstract protected function prepareResult(array &$map, DedupeDataSourceResult $result);

	public function getTotalCount()
	{
		$subQuery = new Main\Entity\Query(Entity\DuplicateEntityMatchHashTable::getEntity());

		$subQuery->addGroup('MATCH_HASH');
		$subQuery->registerRuntimeField('', new Main\Entity\ExpressionField('QTY', 'COUNT(*)'));
		$subQuery->addFilter('>QTY', 1);

		$typeID = $this->typeID;
		$entityTypeID = $this->getEntityTypeID();
		$scope = $this->getScope();
		$enablePermissionCheck = $this->isPermissionCheckEnabled();

		$subQuery->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$subQuery->addFilter('=TYPE_ID', $typeID);

		if ($scope === DuplicateIndexType::DEFAULT_SCOPE)
		{
			$subQuery->addFilter('=SCOPE', DuplicateIndexType::DEFAULT_SCOPE);
		}
		else
		{
			$subQuery->addFilter('@SCOPE', array(DuplicateIndexType::DEFAULT_SCOPE, $scope));
		}

		if($enablePermissionCheck)
		{
			$permissionSql = $this->preparePermissionSql();
			if($permissionSql === false)
			{
				//Access denied;
				return 0;
			}
			if($permissionSql !== '')
			{
				$subQuery->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
			}
		}

		$query = new Main\Entity\Query($subQuery);
		$query->registerRuntimeField('', new Main\Entity\ExpressionField('QTY', 'COUNT(*)'));
		$query->addSelect('QTY');

		$fields = $query->exec()->fetch();
		return  is_array($fields) && isset($fields['QTY']) ? (int)$fields['QTY'] : 0;
	}


	public function getDuplicateByMatchHash(string $matchHash): ?Duplicate
	{
		$result = $this->getListInternal(['=MATCH_HASH' => $matchHash],0, 1);
		return $result->getItem($matchHash);
	}

	/**
	 * @return DedupeDataSourceResult
	 */
	public function getList($offset, $limit)
	{
		return $this->getListInternal([], $offset, $limit);
	}
	protected function getListInternal(array $filter, $offset, $limit)
	{
		$result = new DedupeDataSourceResult();

		$typeID = $this->typeID;
		$entityTypeID = $this->getEntityTypeID();
		$enablePermissionCheck = $this->isPermissionCheckEnabled();
		$scope = $this->getScope();
		//$userID = $this->getUserID();

		$query = Entity\DuplicateEntityMatchHashTable::query();

		$query->setFilter($filter);

		$query->addSelect('MATCH_HASH');
		$query->addGroup('MATCH_HASH');
		$query->addOrder('MATCH_HASH', 'ASC');

		$query->registerRuntimeField('', new Main\Entity\ExpressionField('QTY', 'COUNT(*)'));
		$query->addSelect('QTY');
		$query->addFilter('>QTY', 1);

		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=TYPE_ID', $typeID);

		if ($this->params->getIndexDate())
		{
			$query->registerRuntimeField('',
				new Main\Entity\ExpressionField('MAX_HASH_DATE_MODIFY',
					new Main\DB\SqlExpression('MAX(?#.?#)', $query->getInitAlias(), 'DATE_MODIFY')
				)
			);
			$query->addFilter('>=MAX_HASH_DATE_MODIFY', $this->params->getIndexDate()->format("Y-m-d H:i:s"));
		}

		$permissionSql = '';
		if($enablePermissionCheck)
		{
			$permissionSql = $this->preparePermissionSql();
			if($permissionSql === false)
			{
				//Access denied;
				return $result;
			}
			if($permissionSql !== '')
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
					'=ref.USER_ID' => new Main\DB\SqlExpression('?i', $this->getUserID()),
					'=ref.ENTITY_TYPE_ID' => new Main\DB\SqlExpression('?i', $this->getEntityTypeID()),
					'=ref.SCOPE' => new Main\DB\SqlExpression('?s', $this->getScope()),
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

		if(!is_int($offset))
		{
			$offset = (int)$offset;
		}
		if($offset > 0)
		{
			$query->setOffset($offset);
		}

		if(!is_int($limit))
		{
			$limit = (int)$limit;
		}
		if($limit > 0)
		{
			$query->setLimit($limit);
		}

		$dbResult = $query->exec();

		$processedItemCount = 0;
		$lightHashes = array();
		$heavyHashes = array();
		while($fields = $dbResult->fetch())
		{
			$processedItemCount++;

			$quantity = isset($fields['QTY']) ? (int)$fields['QTY'] : 0;
			$matchHash = isset($fields['MATCH_HASH']) ? $fields['MATCH_HASH'] : '';
			if($matchHash === '' || $quantity < 2)
			{
				$result->addInvalidItem($matchHash);
				continue;
			}

			if($quantity <= 100)
			{
				$lightHashes[] = $matchHash;
			}
			else
			{
				$heavyHashes[] = $matchHash;
			}
		}
		$result->setProcessedItemCount($processedItemCount);

		$map = array();
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

				if($enablePermissionCheck && $permissionSql !== '')
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

			if($enablePermissionCheck && $permissionSql !== '')
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

				$matchHash = isset($fields['MATCH_HASH']) ? $fields['MATCH_HASH'] : '';
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
