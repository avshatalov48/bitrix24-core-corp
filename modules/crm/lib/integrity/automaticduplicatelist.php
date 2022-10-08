<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Main;
use Bitrix\Crm\Integrity\Entity\AutomaticDuplicateIndexTable;
use CCrmOwnerType;

class AutomaticDuplicateList extends DuplicateList
{
	protected $isDirty = null;

	public function setIsDirty(bool $isDirty): void
	{
		$this->isDirty = $isDirty;
	}

	public function getIsDirty()
	{
		return $this->isDirty;
	}

	public function isAutomatic(): bool
	{
		return true;
	}

	public static function getTotalItems($userID, $entityTypeID, array $typeIDs, $scope)
	{
		return AutomaticDuplicateIndexTable::query()
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
					Entity\AutomaticDuplicateIndexTable::getEntity(),
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

	public function getRootItemCount()
	{
		$typeIDs = $this->getTypeIDs();
		if (empty($typeIDs))
		{
			throw new Main\NotSupportedException("Criterion types are required.");
		}

		$query = new Main\Entity\Query(Entity\AutomaticDuplicateIndexTable::getEntity());
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

		$query = AutomaticDuplicateIndexTable::query();
		$query->addSelect('ID');
		$query->addSelect('ROOT_ENTITY_ID');
		$query->addSelect('ROOT_ENTITY_NAME');
		$query->addSelect('ROOT_ENTITY_TITLE');
		$query->addSelect('QUANTITY');
		$query->addSelect('TYPE_ID');
		$query->addSelect('SCOPE');
		$query->addSelect('MATCHES');

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
		if ($this->isDirty !== null)
		{
			$query->where('IS_DIRTY', $this->isDirty);
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
				$query->addOrder('ROOT_ENTITY_EMAIL', $order);
			}
			elseif ($this->sortTypeID === DuplicateIndexType::COMMUNICATION_PHONE)
			{
				$query->addOrder('ROOT_ENTITY_PHONE', $order);
			}
			elseif ($this->sortTypeID === DuplicateIndexType::PERSON)
			{
				$query->addOrder('ROOT_ENTITY_NAME', $order);
			}
			elseif ($this->sortTypeID === DuplicateIndexType::ORGANIZATION)
			{
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
					$query->addOrder("ROOT_ENTITY_{$typeName}", $order);
				}
			}
		}
		elseif ($this->isNaturalSortEnabled())
		{
			$order = $this->sortOrder === SORT_DESC ? 'DESC' : 'ASC';
			$query->addOrder('STATUS_ID', $order);
			$query->addOrder('ID', $order);
		}

		return $query;
	}
}
