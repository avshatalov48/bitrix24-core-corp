<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
use Bitrix\Crm;
abstract class DuplicateCriterion
{
	protected $useStrictComparison = false;

	protected $sortDescendingByEntityTypeId = false;

	public function isStrictComparison()
	{
		return $this->useStrictComparison;
	}
	public function setStrictComparison($useStrictComparison)
	{
		if(!is_bool($useStrictComparison))
		{
			throw new Main\ArgumentTypeException('useStrictComparison', 'boolean');
		}

		$this->useStrictComparison = $useStrictComparison;
	}

	abstract public function find($entityTypeID = \CCrmOwnerType::Undefined, $limit = 50);
	abstract public function equals(DuplicateCriterion $item);
	abstract public function getTypeName();
	abstract public function getMatches();
	abstract public function getMatchHash();
	abstract public function getMatchDescription();
	abstract public function getIndexTypeID();
	abstract public function getTextTotals($count, $limit = 0);

	public function getScope()
	{
		return DuplicateIndexType::DEFAULT_SCOPE;
	}

	public function getActualCount($entityTypeID, $rootEntityID, $userID, $enablePermissionCheck = false, $limit = 0)
	{
		$dataSource = DedupeDataSource::create($this->getIndexTypeID(), new DedupeParams($entityTypeID, $userID, $enablePermissionCheck, $this->getScope()));
		return $dataSource->calculateEntityCount($this, array('ROOT_ENTITY_ID' => $rootEntityID, 'LIMIT' => $limit));
	}
	/**
	* @return Duplicate
	*/
	public function createDuplicate($entityTypeID, $rootEntityID, $userID, $enablePermissionCheck, $enableRanking, $limit = 0)
	{
		if($entityTypeID !== \CCrmOwnerType::Lead
			&& $entityTypeID !== \CCrmOwnerType::Contact
			&& $entityTypeID !== \CCrmOwnerType::Company)
		{
			throw new Main\NotSupportedException("Entity type: '".\CCrmOwnerType::ResolveName($entityTypeID)."' is not supported in current context");
		}

		/** @var Duplicate $dup **/
		$dup = new Duplicate($this, array());

		$query = static::createQuery();
		$query->addSelect('ENTITY_ID');
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		static::setQueryFilter($query, $this->getMatches());

		if($enablePermissionCheck)
		{
			$permissions = isset($params['PERMISSIONS']) ? $params['PERMISSIONS'] : null;
			if($permissions === null)
			{
				$permissions = \CCrmPerms::GetUserPermissions($userID);
			}

			$permissionSql = \CCrmPerms::BuildSql(
				\CCrmOwnerType::ResolveName($entityTypeID),
				'',
				'READ',
				array('RAW_QUERY' => true, 'PERMS'=> $permissions)
			);

			if($permissionSql === false)
			{
				//Access denied;
				return null;
			}

			if($permissionSql !== '')
			{
				$query->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
			}
		}

		if($limit > 0)
		{
			$query->setLimit($limit);
		}

		if($rootEntityID > 0)
		{
			$dup->setRootEntityID($rootEntityID);

			$query->addFilter('!ENTITY_ID', $rootEntityID);
			$query->addFilter(
				'!@ENTITY_ID',
				DuplicateIndexMismatch::prepareQueryField($this, $entityTypeID, $rootEntityID, $userID)
			);
		}

		$dbResult = $query->exec();
		$rankings = array();
		while($fields = $dbResult->fetch())
		{
			$entityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
			if($entityID <= 0)
			{
				continue;
			}

			$entity =  new DuplicateEntity($entityTypeID, $entityID);
			$entity->setCriterion($this);
			if($enableRanking)
			{
				$rankings[] = $entity->getRanking();
			}
			$dup->addEntity($entity);
		}
		$this->onAfterDuplicateCreated($dup, $entityTypeID, $userID, $enablePermissionCheck, $enableRanking, $rankings);

		if($enableRanking)
		{
			DuplicateEntityRanking::initializeBulk($rankings,
				array('CHECK_PERMISSIONS' => $enablePermissionCheck, 'USER_ID' => $userID)
			);
		}
		return $dup;
	}
	protected function onAfterDuplicateCreated(Duplicate $dup, $entityTypeID, $userID, $enablePermissionCheck, $enableRanking, array &$rankings)
	{
	}
	public static function prepareMatchHash(array $matches)
	{
		throw new Main\NotImplementedException('Method prepareMatchHash must be overridden');
	}
	/**
	 * Prepare duplicate search query
	 * @param \CCrmOwnerType $entityTypeID Target Entity Type ID
	 * @param int $limit Limit of result query
	 * @return Main\Entity\Query
	 * @throws Main\NotImplementedException
	 */
	public function prepareSearchQuery($entityTypeID = \CCrmOwnerType::Undefined, array $select = null, array $order = null, $limit = 0)
	{
		throw new Main\NotImplementedException('Method prepareSearchQuery must be overridden');
	}

	/**
	 * Sort descending by entity ID.
	 * @param bool $mode Mode.
	 * @return $this
	 */
	public function sortDescendingByEntityTypeId($mode = true)
	{
		$this->sortDescendingByEntityTypeId = $mode;
		return $this;
	}

	/**
	* @return Main\Entity\Query
	*/
	protected static function createQuery()
	{
		throw new Main\NotImplementedException('Method createQuery must be overridden');
	}
	protected static function setQueryFilter(Main\Entity\Query $query, array $matches)
	{
		throw new Main\NotImplementedException('Method injectMatches must be overridden');
	}
}