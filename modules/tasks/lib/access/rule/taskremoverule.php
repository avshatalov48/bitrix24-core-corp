<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Main\Access\AccessibleItem;

class TaskRemoveRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!$task)
		{
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		if (
			$task->isMember($this->user->getUserId(), RoleDictionary::ROLE_DIRECTOR)
			&& $this->user->getPermission(PermissionDictionary::TASK_DIRECTOR_DELETE)
		)
		{
			return true;
		}

		if (array_intersect($task->getMembers(RoleDictionary::ROLE_DIRECTOR), $this->user->getAllSubordinates()))
		{
			return true;
		}

		$isInDepartment = $task->isInDepartment($this->user->getUserId(), false, [RoleDictionary::ROLE_RESPONSIBLE, RoleDictionary::ROLE_DIRECTOR, RoleDictionary::ROLE_ACCOMPLICE]);

		if (
			$this->user->getPermission(PermissionDictionary::TASK_DEPARTMENT_DELETE)
			&& $isInDepartment
		)
		{
			return true;
		}

		if (
			$this->user->getPermission(PermissionDictionary::TASK_NON_DEPARTMENT_DELETE)
			&& !$isInDepartment
		)
		{
			return true;
		}

		// task not in group
		if (!$task->getGroupId())
		{
			return false;
		}

		// task in group
		return Loader::includeModule("socialnetwork")
			&& \CSocNetFeaturesPerms::CanPerformOperation($this->user->getUserId(), SONET_ENTITY_GROUP, $task->getGroupId(), "tasks", "delete_tasks");
	}
}