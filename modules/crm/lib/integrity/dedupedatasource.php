<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

abstract class DedupeDataSource
{
	protected $typeID = DuplicateIndexType::UNDEFINED;
	protected DedupeParams $params;
	protected $permissionSql;
	protected $processedItemCount = 0;

	abstract public function getList($offset, $limit): DedupeDataSourceResult;
	abstract protected function getOrmEntity(): ORM\Entity;
	abstract protected function applyQueryFilterByMatches(Query $query, DuplicateCriterion $criterion): Query;

	/**
	 * @deprecated
	 * @see DedupeDataSource::isEmptyEntity()
	 */
	abstract public function calculateEntityCount(DuplicateCriterion $criterion, array $options = null);

	public function __construct($typeID, DedupeParams $params)
	{
		$this->typeID = $typeID;
		$this->params = $params;
	}

	public function getTypeID()
	{
		return $this->typeID;
	}

	/**
	 * @return DedupeParams
	 */
	public function getParams()
	{
		return $this->params;
	}

	public function getEntityTypeID()
	{
		return $this->params->getEntityTypeID();
	}

	public function getUserID()
	{
		return $this->params->getUserID();
	}

	public function isPermissionCheckEnabled()
	{
		return $this->params->isPermissionCheckEnabled();
	}

	public function getScope()
	{
		return $this->params->getScope();
	}

	public function getContextId(): string
	{
		return $this->params->getContextId();
	}

	public function isEmptyEntity(DuplicateCriterion $criterion, int $rootEntityId): bool
	{
		$query = new Query($this->getOrmEntity());
		$query->addSelect('ID');

		if ($this->isPermissionCheckEnabled())
		{
			$permissionSql = $this->preparePermissionSql();
			if ($permissionSql === false)
			{
				return true; //Access denied;
			}

			if (is_string($permissionSql) && $permissionSql !== '')
			{
				$query->addFilter('@ENTITY_ID', new SqlExpression($permissionSql));
			}
		}

		$entityTypeId = $this->getEntityTypeID();
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeId);
		$query = $this->applyQueryFilterByMatches($query, $criterion);

		if ($rootEntityId > 0)
		{
			$userId = $this->getUserID();

			$query->addFilter('!ENTITY_ID', $rootEntityId);
			$query->addFilter(
				'!@ENTITY_ID',
				DuplicateIndexMismatch::prepareQueryField(
					$criterion,
					$entityTypeId,
					$rootEntityId,
					$userId
				)
			);
		}

		$query = self::registerRuntimeFieldsByParams($query, $this->getParams());
		$query->setLimit(1);
		$dbResult = $query->exec();
		$fields = $dbResult->fetch();

		return !(is_array($fields) && isset($fields['ID']));
	}

	protected function preparePermissionSql()
	{
		if ($this->permissionSql !== null)
		{
			return $this->permissionSql;
		}

		$userID = $this->getUserID();
		if (\CCrmPerms::IsAdmin($userID))
		{
			$this->permissionSql = '';
		}
		else
		{
			$this->permissionSql = \CCrmPerms::BuildSql(
				\CCrmOwnerType::ResolveName($this->getEntityTypeID()),
				'',
				'READ',
				array('RAW_QUERY' => true, 'PERMS'=> \CCrmPerms::GetUserPermissions($userID))
			);
		}

		return $this->permissionSql;
	}

	protected static function getDataManagerClass(int $entityTypeId): string
	{
		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				$entityClass = \Bitrix\Crm\LeadTable::class;
				break;
			case \CCrmOwnerType::Deal:
				$entityClass = \Bitrix\Crm\DealTable::class;
				break;
			case \CCrmOwnerType::Contact:
				$entityClass = \Bitrix\Crm\ContactTable::class;
				break;
			case \CCrmOwnerType::Company:
				$entityClass = \Bitrix\Crm\CompanyTable::class;
				break;
			default:
				throw new \Bitrix\Main\NotImplementedException("Entity type #{$entityTypeId} has not data manager");
		}

		return $entityClass;
	}

	public static function create($typeID, DedupeParams $params)
	{
		return DedupeDataSourceFactory::create($typeID, $params);
	}

	public static function registerRuntimeFieldsByParams(Query $query, DedupeParams $params): Query
	{
		$categoryId = $params->getCategoryId();
		$isJoinedToEntity = false;

		// in automatic mode we are looking for items assigned to current user only:
		if ($params->limitByAssignedUser())
		{
			$isJoinedToEntity = true;
			$query->registerRuntimeField(
				'',
				static::getAssignedByReferenceField(
					$params->getEntityTypeID(),
					$params->getUserID(),
					$categoryId
				)
			);
		}

		// using entity category ID to correct filter data
		if (isset($categoryId) && !$isJoinedToEntity)
		{
			$query->registerRuntimeField(
				'',
				static::getCategoryReferenceField($params->getEntityTypeID(), $categoryId));
		}

		return $query;
	}

	public static function getAssignedByReferenceField(int $entityTypeId, int $userId, ?int $categoryId = null): Reference
	{
		$referenceFilter = [
			'=this.ENTITY_ID' => 'ref.ID',
			'ref.ASSIGNED_BY_ID' => new SqlExpression('?i', $userId),
		];

		if (isset($categoryId))
		{
			$referenceFilter[] = ['ref.CATEGORY_ID' => new SqlExpression('?', $categoryId)];
		}

		return new Reference(
			'ASSIGNED_BY_JOINED_ENTITY',
			static::getDataManagerClass($entityTypeId),
			$referenceFilter,
			['join_type' => Join::TYPE_INNER]
		);
	}

	public static function getCategoryReferenceField(int $entityTypeId, int $categoryId): Reference
	{
		return new Reference(
			'CATEGORY_JOINED_ENTITY',
			static::getDataManagerClass($entityTypeId),
			[
				'=this.ENTITY_ID' => 'ref.ID',
				'ref.CATEGORY_ID' => new SqlExpression('?', $categoryId)
			],
			['join_type' => Join::TYPE_INNER]
		);
	}
}
