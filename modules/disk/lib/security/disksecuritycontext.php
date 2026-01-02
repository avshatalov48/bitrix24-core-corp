<?php

namespace Bitrix\Disk\Security;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\SimpleRightTable;
use Bitrix\Disk\RightsManager;
use Bitrix\Disk\Access\AccessCodeEnum;
use Bitrix\Main\UserAccessTable;
use CAccess;

class DiskSecurityContext extends SecurityContext
{
	/** @var  array */
	protected $operationsCache = array();

	/**
	 * @param $objectId
	 * @param $operation
	 * @return bool
	 */
	protected function canDoOperation($objectId, $operation)
	{
		if ($this->userId === static::GUEST_USER)
		{
			return false;
		}

		if (!isset($this->operationsCache[$objectId]))
		{
			$this->operationsCache[$objectId] = $this->getOperationsByObject($objectId);
		}

		return isset($this->operationsCache[$objectId][$operation]);
	}

	protected function getOperationsByObject($objectId)
	{
		$access = new CAccess;
		/** @noinspection PhpParamsInspection */
		$access->updateCodes(array('USER_ID' => $this->userId));

		return Driver::getInstance()->getRightsManager()->getUserOperationsByObject($objectId, $this->userId);
	}

	public function preloadOperationsForChildren($parentObjectId)
	{
		$rightsManager = Driver::getInstance()->getRightsManager();

		foreach ($rightsManager->getUserOperationsForChildren($parentObjectId, $this->userId) as $objectId => $operations)
		{
			$this->operationsCache[$objectId] = $operations;
		}
		unset($operations);
	}

	public function preloadOperationsForSpecifiedObjects($parentObjectId, array $objectIds)
	{
		$rightsManager = Driver::getInstance()->getRightsManager();
		foreach ($rightsManager->getUserOperationsForChildren($parentObjectId, $this->userId, $objectIds) as $objectId => $operations)
		{
			$this->operationsCache[$objectId] = $operations;
		}
	}

	/**
	 * @param $targetId
	 * @return bool
	 */
	public function canAdd($targetId)
	{
		return $this->canDoOperation($targetId, RightsManager::OP_ADD);
	}

	/**
	 * @param $objectId
	 * @return bool
	 */
	public function canChangeRights($objectId)
	{
		return $this->canDoOperation($objectId, RightsManager::OP_RIGHTS);
	}

	/**
	 * @param $objectId
	 * @return bool
	 */
	public function canChangeSettings($objectId)
	{
		return $this->canDoOperation($objectId, RightsManager::OP_SETTINGS);
	}

	/**
	 * @param $objectId
	 * @return bool
	 */
	public function canCreateWorkflow($objectId)
	{
		return $this->canDoOperation($objectId, RightsManager::OP_CREATE_WF);
	}

	/**
	 * @param $objectId
	 * @return bool
	 */
	public function canDelete($objectId)
	{
		return $this->canDoOperation($objectId, RightsManager::OP_DESTROY);
	}

	/**
	 * @param $objectId
	 * @return bool
	 */
	public function canMarkDeleted($objectId)
	{
		return $this->canDoOperation($objectId, RightsManager::OP_DELETE);
	}

	/**
	 * @param $objectId
	 * @param $targetId
	 * @return bool
	 */
	public function canMove($objectId, $targetId)
	{
		return $this->canRead($objectId) && $this->canMarkDeleted($objectId) && $this->canAdd($targetId);
	}

	/**
	 * @param $objectId
	 * @return bool
	 */
	public function canRead($objectId)
	{
		return $this->canDoOperation($objectId, RightsManager::OP_READ);
	}

	/**
	 * @param $objectId
	 * @return bool
	 */
	public function canRename($objectId)
	{
		return $this->canDoOperation($objectId, RightsManager::OP_EDIT);
	}

	/**
	 * @param $objectId
	 * @return bool
	 */
	public function canRestore($objectId)
	{
		return $this->canDoOperation($objectId, RightsManager::OP_RESTORE);
	}

	/**
	 * @param $objectId
	 * @return bool
	 */
	public function canShare($objectId)
	{
		return $this->canDoOperation($objectId, RightsManager::OP_SHARING);
	}

	/**
	 * @param $objectId
	 * @return bool
	 */
	public function canUpdate($objectId)
	{
		return $this->canDoOperation($objectId, RightsManager::OP_EDIT);
	}

	/**
	 * @param $objectId
	 * @return bool
	 */
	public function canStartBizProc($objectId)
	{
		return $this->canDoOperation($objectId, RightsManager::OP_START_BP);
	}

	public function getSqlExpressionForList($columnObjectId, $columnCreatedBy)
	{
		$userId = (int)$this->userId;

		$tableDiskSimpleRight = SimpleRightTable::getTableName();
		$tableUserAccess = UserAccessTable::getTableName();

		$accessCodeCreator = AccessCodeEnum::CREATOR->value;
		$accessCodeAuthorizedUser = AccessCodeEnum::AUTHORIZED_USER->value;

		return  <<<SQL
			EXISTS (
				SELECT 1
				FROM $tableDiskSimpleRight simple_right
				WHERE simple_right.OBJECT_ID = $columnObjectId
				  AND (
					  (simple_right.ACCESS_CODE = '$accessCodeCreator' AND $columnCreatedBy = $userId)
		
					  OR simple_right.ACCESS_CODE = '$accessCodeAuthorizedUser'
		
					  OR simple_right.ACCESS_CODE IN (
						  SELECT ACCESS_CODE
						  FROM $tableUserAccess
						  WHERE USER_ID = $userId
					  )
				  )
			)
		SQL;
	}
}