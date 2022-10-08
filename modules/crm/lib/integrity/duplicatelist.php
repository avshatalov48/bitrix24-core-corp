<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Main;
use CCrmOwnerType;

class DuplicateList
{
	protected $typeID = DuplicateIndexType::UNDEFINED;
	protected $scope = DuplicateIndexType::DEFAULT_SCOPE;
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected $matchHash = '';
	protected $statusIDs = array();

	protected $userID = 0;
	protected $enablePermissionCheck = false;
	protected $enableRanking = false;
	protected $sortTypeID = DuplicateIndexType::UNDEFINED;
	protected $sortOrder = SORT_ASC;
	protected $useNaturalSort = false;

	public function __construct($typeID, $entityTypeID, $userID, $enablePermissionCheck = false, $options = null)
	{
		$this->setTypeID($typeID);
		$this->setEntityTypeID($entityTypeID);
		$this->setUserID($userID);
		$this->enabledPermissionCheck($enablePermissionCheck);

		if (is_array($options))
		{
			if (isset($options['SCOPE']))
			{
				if (!DuplicateIndexType::checkScopeValue($options['SCOPE']))
				{
					throw new Main\ArgumentException("Option has invalid value", 'SCOPE');
				}

				$this->setScope($options['SCOPE']);
			}
		}
	}

	public function getTypeID()
	{
		return $this->typeID;
	}

	public function setTypeID($typeID)
	{
		if (!is_numeric($typeID))
		{
			throw new Main\ArgumentTypeException('typeID', 'integer');
		}

		if (!is_int($typeID))
		{
			$typeID = intval($typeID);
		}

		$this->typeID = $typeID;
	}

	public function getScope()
	{
		return $this->scope;
	}

	public function setScope($scope)
	{
		if (!DuplicateIndexType::checkScopeValue($scope))
		{
			throw new Main\ArgumentException("Parameter has invalid value", 'scope');
		}

		$this->scope = $scope;
	}

	public function getStatusIDs()
	{
		return $this->statusIDs;
	}

	public function setStatusIDs(array $statusIDs)
	{
		$this->statusIDs = $statusIDs;
	}

	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}

	public function setEntityTypeID($entityTypeID)
	{
		if (!is_numeric($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if (!is_int($entityTypeID))
		{
			$entityTypeID = intval($entityTypeID);
		}

		if ($entityTypeID !== \CCrmOwnerType::Lead
			&& $entityTypeID !== \CCrmOwnerType::Contact
			&& $entityTypeID !== \CCrmOwnerType::Company)
		{
			throw new Main\NotSupportedException("Criterion type(s): '" . \CCrmOwnerType::ResolveName($entityTypeID) . "' is not supported in current context.");
		}

		$this->entityTypeID = $entityTypeID;
	}

	public function getMatchHash()
	{
		return $this->matchHash;
	}

	public function setMatchHash($matchHash)
	{
		$this->matchHash = $matchHash;
	}

	public function getUserID()
	{
		return $this->userID;
	}

	public function setUserID($userID)
	{
		if (!is_integer($userID))
		{
			$userID = intval($userID);
		}
		$userID = max($userID, 0);

		if ($this->userID === $userID)
		{
			return;
		}

		$this->userID = $userID;
	}

	public function isPermissionCheckEnabled()
	{
		return $this->enablePermissionCheck;
	}

	public function enabledPermissionCheck($enable)
	{
		$this->enablePermissionCheck = is_bool($enable) ? $enable : (bool)$enable;
	}

	public function isRankingEnabled()
	{
		return $this->enableRanking;
	}

	public function enabledRanking($enable)
	{
		$this->enableRanking = is_bool($enable) ? $enable : (bool)$enable;
	}

	public function getSortTypeID()
	{
		return $this->sortTypeID;
	}

	public function setSortTypeID($typeID)
	{
		if (!is_numeric($typeID))
		{
			throw new Main\ArgumentTypeException('typeID', 'integer');
		}

		if (!is_int($typeID))
		{
			$typeID = intval($typeID);
		}

		if (!DuplicateIndexType::isSingle($typeID))
		{
			throw new Main\NotSupportedException("Criterion type(s): '" . DuplicateIndexType::resolveName($typeID) . "' is not supported in current context. Please use single type for sorting.");
		}
		$this->sortTypeID = $typeID;
	}

	public function isNaturalSortEnabled(): bool
	{
		return $this->useNaturalSort;
	}

	public function enableNaturalSort(bool $useNaturalSort): void
	{
		$this->useNaturalSort = $useNaturalSort;
	}

	public function getSortOrder()
	{
		return $this->sortOrder;
	}

	public function setSortOrder($sortOrder)
	{
		if (!is_numeric($sortOrder))
		{
			throw new Main\ArgumentTypeException('sortOrder', 'integer');
		}

		if (!is_int($sortOrder))
		{
			$sortOrder = intval($sortOrder);
		}

		if ($sortOrder !== SORT_DESC && $sortOrder !== SORT_ASC)
		{
			throw new Main\ArgumentOutOfRangeException('sortOrder', SORT_DESC, SORT_ASC);
		}

		$this->sortOrder = $sortOrder;
	}

	public function isSortingEnabled()
	{
		return $this->sortTypeID !== DuplicateIndexType::UNDEFINED;
	}

	/**
	 * @return Main\Entity\Query
	 */
	protected function createQuery($offset = 0, $limit = 0)
	{
		if (!is_int($offset))
		{
			$offset = intval($offset);
		}

		if (!is_int($limit))
		{
			$limit = intval($limit);
		}

		$typeIDs = $this->getTypeIDs();
		if (empty($typeIDs))
		{
			throw new Main\NotSupportedException("Criterion types are required.");
		}

		$query = new Main\Entity\Query(Entity\DuplicateIndexTable::getEntity());
		$query->addSelect('ROOT_ENTITY_ID');
		$query->addSelect('ROOT_ENTITY_NAME');
		$query->addSelect('ROOT_ENTITY_TITLE');
		$query->addSelect('QUANTITY');
		$query->addSelect('TYPE_ID');
		$query->addSelect('SCOPE');
		$query->addSelect('MATCHES');
		$query->addSelect('IS_JUNK');

		$permissionSql = '';
		if ($this->enablePermissionCheck)
		{
			$permissions = \CCrmPerms::GetUserPermissions($this->userID);
			$permissionSql = \CCrmPerms::BuildSql(
				\CCrmOwnerType::ResolveName($this->entityTypeID),
				'',
				'READ',
				array('RAW_QUERY' => true, 'PERMS' => $permissions)
			);

			if ($permissionSql === false)
			{
				//Access denied;
				return null;
			}
		}

		$query->addFilter('=USER_ID', $this->userID);
		$query->addFilter('=ENTITY_TYPE_ID', $this->entityTypeID);
		$query->addFilter('@TYPE_ID', $typeIDs);
		if ($this->matchHash != '')
		{
			$query->addFilter('=MATCH_HASH', $this->matchHash);
		}

		$query->addFilter('=SCOPE', $this->scope);

		if (!empty($this->statusIDs))
		{
			$query->addFilter('@STATUS_ID', $this->statusIDs);
		}

		if ($this->enablePermissionCheck && $permissionSql !== '')
		{
			$query->addFilter('@ROOT_ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
		}

		if ($offset > 0)
		{
			$query->setOffset($offset);
		}

		if ($limit > 0)
		{
			$query->setLimit($limit);
		}

		$enableSorting = $this->sortTypeID !== DuplicateIndexType::UNDEFINED;
		if ($enableSorting)
		{
			$order = $this->sortOrder === SORT_DESC ? 'DESC' : 'ASC';

			if (!empty($this->statusIDs))
			{
				$query->addOrder('STATUS_ID', $order);
			}

			if ($this->sortTypeID === DuplicateIndexType::COMMUNICATION_EMAIL)
			{
				$query->addOrder('ROOT_ENTITY_EMAIL_FLAG', $order);
				$query->addOrder('ROOT_ENTITY_EMAIL', $order);
			}
			elseif ($this->sortTypeID === DuplicateIndexType::COMMUNICATION_PHONE)
			{
				$query->addOrder('ROOT_ENTITY_PHONE_FLAG', $order);
				$query->addOrder('ROOT_ENTITY_PHONE', $order);
			}
			elseif ($this->sortTypeID === DuplicateIndexType::PERSON)
			{
				$query->addOrder('ROOT_ENTITY_NAME_FLAG', $order);
				$query->addOrder('ROOT_ENTITY_NAME', $order);
			}
			elseif ($this->sortTypeID === DuplicateIndexType::ORGANIZATION)
			{
				$query->addOrder('ROOT_ENTITY_TITLE_FLAG', $order);
				$query->addOrder('ROOT_ENTITY_TITLE', $order);
			}
			else
			{
				$isSortingTypeFound = false;
				$sortingTypeID = DuplicateIndexType::UNDEFINED;
				foreach (DuplicateRequisiteCriterion::getSupportedDedupeTypes() as $typeID)
				{
					if ($this->sortTypeID === $typeID)
					{
						$sortingTypeID = $typeID;
						$isSortingTypeFound = true;
						break;
					}
				}
				if (!$isSortingTypeFound)
				{
					foreach (DuplicateBankDetailCriterion::getSupportedDedupeTypes() as $typeID)
					{
						if ($this->sortTypeID === $typeID)
						{
							$sortingTypeID = $typeID;
							$isSortingTypeFound = true;
							break;
						}
					}
				}
				if ($isSortingTypeFound)
				{
					$typeName = DuplicateIndexType::resolveName($sortingTypeID);
					$query->addOrder("ROOT_ENTITY_{$typeName}_FLAG", $order);
					$query->addOrder("ROOT_ENTITY_{$typeName}", $order);
				}
			}
		}
		elseif ($this->isNaturalSortEnabled())
		{
			$order = $this->sortOrder === SORT_DESC ? 'DESC' : 'ASC';
			$query->addOrder('STATUS_ID', $order);
			$query->addOrder('USER_ID', $order);
			$query->addOrder('ENTITY_TYPE_ID', $order);
			$query->addOrder('TYPE_ID', $order);
			$query->addOrder('MATCH_HASH', $order);
			$query->addOrder('SCOPE', $order);
		}
		return $query;
	}

	/**
	 * @return Duplicate[]
	 */
	public function getRootItems($offset = 0, $limit = 0)
	{
		$query = $this->createQuery($offset, $limit);
		$dbResult = $query->exec();

		$results = array();
		while ($fields = $dbResult->fetch())
		{
			$results[] = $this->createDuplicate($fields);
		}
		return $results;
	}

	/**
	 * @return Boolean
	 */
	public function isJunk($entityID)
	{
		$query = $this->createQuery(0, 0);
		if (!$query)
		{
			throw new Main\InvalidOperationException("Could not create DB query.");
		}

		$query->addFilter('=ROOT_ENTITY_ID', $entityID);
		$dbResult = $query->exec();

		$fields = $dbResult->fetch();
		return is_array($fields) && isset($fields['IS_JUNK']) && mb_strtoupper($fields['IS_JUNK']) === 'Y';
	}

	public function isAutomatic(): bool
	{
		return false;
	}

	private function createDuplicate(array &$fields)
	{
		$rootEntityID = isset($fields['ROOT_ENTITY_ID']) ? (int)$fields['ROOT_ENTITY_ID'] : 0;
		$typeID = isset($fields['TYPE_ID']) ? (int)$fields['TYPE_ID'] : 0;
		$matches = isset($fields['MATCHES']) ? $fields['MATCHES'] : '';
		$matches = $matches !== '' ? unserialize($matches, ['allowed_classes' => false]) : null;
		if(!is_array($matches))
		{
			$matches = array();
		}
		$quantity = isset($fields['QUANTITY']) ? (int)$fields['QUANTITY'] : 0;

		$result = new Duplicate($this->createCriterion($typeID, $matches), []);
		$result->setRootEntityID($rootEntityID);
		if (isset($fields['ID']))
		{
			$result->setQueueId($fields['ID']);
		}

		$isJunk = isset($fields['IS_JUNK']) && mb_strtoupper($fields['IS_JUNK']) === 'Y';
		if ($isJunk)
		{
			$result->markAsJunk(true);
			//Try to supply more information for junked item (if root entity is already deleted)
			$rootPersName = isset($fields['ROOT_ENTITY_NAME']) ? $fields['ROOT_ENTITY_NAME'] : '';
			if ($rootPersName !== '')
			{
				$names = explode(' ', $rootPersName);
				$qty = count($names);
				for ($i = 0; $i < $qty; $i++)
				{
					$names[$i] = ucfirst($names[$i]);
				}
				$result->setRootPersonName(implode(' ', $names));
			}

			$rootOrgTitle = isset($fields['ROOT_ENTITY_TITLE']) ? $fields['ROOT_ENTITY_TITLE'] : '';
			if ($rootOrgTitle !== '')
			{
				$result->setRootOrganizationTitle(ucfirst($rootOrgTitle));
			}
		}

		$result->setTotalEntityCount($quantity);
		return $result;
	}

	protected function createCriterion($typeID, $matches)
	{
		$criterion = DuplicateManager::createCriterion($typeID, $matches);
		$criterion->setLimitByAssignedUser($this->isAutomatic());
		return $criterion;
	}

	protected function getTypeIDs()
	{
		$result = array();
		if (($this->typeID & DuplicateIndexType::PERSON) !== 0)
		{
			$result[] = DuplicateIndexType::PERSON;
		}
		if (($this->typeID & DuplicateIndexType::ORGANIZATION) !== 0)
		{
			$result[] = DuplicateIndexType::ORGANIZATION;
		}
		if (($this->typeID & DuplicateIndexType::COMMUNICATION_PHONE) !== 0)
		{
			$result[] = DuplicateIndexType::COMMUNICATION_PHONE;
		}
		if (($this->typeID & DuplicateIndexType::COMMUNICATION_EMAIL) !== 0)
		{
			$result[] = DuplicateIndexType::COMMUNICATION_EMAIL;
		}
		foreach (DuplicateRequisiteCriterion::getSupportedDedupeTypes() as $typeID)
		{
			if (($this->typeID & $typeID) !== 0)
			{
				$result[] = $typeID;
			}
		}
		foreach (DuplicateBankDetailCriterion::getSupportedDedupeTypes() as $typeID)
		{
			if (($this->typeID & $typeID) !== 0)
			{
				$result[] = $typeID;
			}
		}
		foreach (DuplicateVolatileCriterion::getSupportedDedupeTypes() as $typeID)
		{
			if (($this->typeID & $typeID) !== 0)
			{
				$result[] = $typeID;
			}
		}
		return $result;
	}

	public function getRootItemCount()
	{
		$typeIDs = $this->getTypeIDs();
		if (empty($typeIDs))
		{
			throw new Main\NotSupportedException("Criterion types are required.");
		}

		$query = new Main\Entity\Query(Entity\DuplicateIndexTable::getEntity());
		$query->registerRuntimeField('', new Main\Entity\ExpressionField('CNT', 'COUNT(*)'));
		$query->addSelect('CNT');

		$permissionSql = '';
		if ($this->enablePermissionCheck)
		{
			$permissions = \CCrmPerms::GetUserPermissions($this->userID);
			$permissionSql = \CCrmPerms::BuildSql(
				\CCrmOwnerType::ResolveName($this->entityTypeID),
				'',
				'READ',
				array('RAW_QUERY' => true, 'PERMS' => $permissions)
			);

			if ($permissionSql === false)
			{
				//Access denied;
				return null;
			}
		}

		$query->addFilter('=USER_ID', $this->userID);
		$query->addFilter('=ENTITY_TYPE_ID', $this->entityTypeID);
		$query->addFilter('@TYPE_ID', $typeIDs);
		if ($this->matchHash != '')
		{
			$query->addFilter('=MATCH_HASH', $this->matchHash);
		}

		$query->addFilter('=SCOPE', $this->scope);

		if (!empty($this->statusIDs))
		{
			$query->addFilter('@STATUS_ID', $this->statusIDs);
		}

		if ($this->enablePermissionCheck && $permissionSql !== '')
		{
			$query->addFilter('@ROOT_ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
		}

		$dbResult = $query->exec();
		$fields = $dbResult->fetch();
		return is_array($fields) && isset($fields['CNT']) ? (int)$fields['CNT'] : 0;
	}

	public static function getTotalItems($userID, $entityTypeID, array $typeIDs, $scope)
	{
		return Entity\DuplicateIndexTable::query()
			->where('USER_ID', $userID)
			->where('ENTITY_TYPE_ID', $entityTypeID)
			->whereIn('TYPE_ID', $typeIDs)
			->where('SCOPE', $scope)
			->queryCountTotal();
	}

	public static function getTotalEntityCount($userID, $entityTypeID, array $typeIDs, $scope)
	{
		$subQuery = Entity\DuplicateEntityMatchHashTable::query();
		$indexJoinConditions = array(
			'=this.MATCH_HASH' => 'ref.MATCH_HASH',
			'=this.ENTITY_TYPE_ID' => 'ref.ENTITY_TYPE_ID',
			'=this.TYPE_ID' => 'ref.TYPE_ID',
			'=ref.USER_ID' => new Main\DB\SqlExpression('?i', $userID),
			'=ref.ENTITY_TYPE_ID' => new Main\DB\SqlExpression('?i', $entityTypeID),
			'=ref.SCOPE' => new Main\DB\SqlExpression('?s', $scope)
		);
		if (!empty($typeIDs))
		{
			$indexJoinConditions['@ref.TYPE_ID'] = new Main\DB\SqlExpression(implode(', ', $typeIDs));
		}

		$subQuery
			->addSelect('ENTITY_ID')
			->registerRuntimeField('',
				new Main\Entity\ReferenceField('I',
					Entity\DuplicateIndexTable::getEntity(),
					$indexJoinConditions,
					array('join_type' => 'INNER')
				)
			)
			->addGroup('ENTITY_ID');

		// workaround for correct filter typed contacts/companies by category ID
		if (in_array($entityTypeID, [CCrmOwnerType::Contact, CCrmOwnerType::Company], true))
		{
			$subQuery->registerRuntimeField('', DedupeDataSource::getCategoryReferenceField($entityTypeID, 0));
		}

		$query = new Main\Entity\Query($subQuery);
		$query
			->registerRuntimeField('', new Main\Entity\ExpressionField('CNT', 'COUNT(*)'))
			->addSelect('CNT');

		$dbResult = $query->exec();
		$fields = $dbResult->fetch();

		return is_array($fields) && isset($fields['CNT']) ? (int)$fields['CNT'] : 0;
	}
}
