<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Model\Member;

use Bitrix\Tasks\Access\Permission\PermissionDictionary;

class ResponsibleList extends BaseList
{

	public function getAccesibleUsers(): ?array
	{
		// department members
		$userIds = $this->getDepartmentMembers();
		$this->getNonDepartmentMembers();
		// department manager
		if ($this->user->getPermission(PermissionDictionary::TASK_DEPARTMENT_MANAGER_DIRECT))
		{
			$userIds = array_merge($userIds, $this->getDepartmentManager());
		}
		// non department managers
		if ($this->user->getPermission(PermissionDictionary::TASK_NON_DEPARTMENT_MANAGER_DIRECT))
		{
			$userIds = array_merge($userIds, $this->getNonDepartmentManager());
		}
		// all other users
		if ($this->user->getPermission(PermissionDictionary::TASK_NON_DEPARTMENT_DIRECT))
		{
			$userIds = array_merge($userIds, $this->getNonDepartmentMembers());
		}

		return $userIds;
	}

	public function getHasRightUsers(): ?array
	{
		return null;
	}
}