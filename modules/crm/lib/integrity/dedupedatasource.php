<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Fields\Relations\Reference;

abstract class DedupeDataSource
{
	protected $typeID = DuplicateIndexType::UNDEFINED;
	/** @var DedupeParams $params **/
	protected $params = null;
	protected $permissionSql = null;
	protected $processedItemCount = 0;

	public function __construct($typeID, DedupeParams $params)
	{
		$this->typeID = $typeID;
		$this->params = $params;
	}
	static public function create($typeID, DedupeParams $params)
	{
		return DedupeDataSourceFactory::create($typeID, $params);
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
	/**
	* @return DedupeDataSourceResult
	*/
	abstract public function getList($offset, $limit);
	abstract public function calculateEntityCount(DuplicateCriterion $criterion, array $options = null);
	protected function preparePermissionSql()
	{
		if($this->permissionSql !== null)
		{
			return $this->permissionSql;
		}

		$userID = $this->getUserID();
		if(\CCrmPerms::IsAdmin($userID))
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

	public static function getAssignedByReferenceField(int $entityTypeId, int $userId): Reference
	{
		return new Reference(
			'ASSIGNED_BY_JOINED_ENTITY',
			static::getDataManagerClass($entityTypeId),
			[
				'=this.ENTITY_ID' => 'ref.ID',
				'ref.ASSIGNED_BY_ID' => new SqlExpression('?i', $userId)
			],
			['join_type' => \Bitrix\Main\ORM\Query\Join::TYPE_INNER]
		);
	}
}