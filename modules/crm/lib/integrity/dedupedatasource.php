<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

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
}