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
use Bitrix\Tasks\Access\Rule\Traits\SubordinateTrait;

class TaskEditRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	use SubordinateTrait;

	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!$task)
		{
			$this->controller->addError(static::class, 'Incorrect task');
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		if (
			$task->getGroupId()
			&& Loader::includeModule("socialnetwork")
			&& \Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry::getInstance()->get(
				$task->getGroupId(),
				'tasks',
				'edit_tasks',
				$this->user->getUserId()
			)
		)
		{
			return true;
		}

		if (
			!$task->isClosed()
			&& $task->isMember($this->user->getUserId(), RoleDictionary::ROLE_DIRECTOR)
		)
		{
			return true;
		}

		if (
			$task->isClosed()
			&& $task->isMember($this->user->getUserId(), RoleDictionary::ROLE_DIRECTOR)
			&& $this->user->getPermission(PermissionDictionary::TASK_CLOSED_DIRECTOR_EDIT)
		)
		{
			return true;
		}

		if (
			$task->isMember($this->user->getUserId(), RoleDictionary::ROLE_RESPONSIBLE)
			&& $this->user->getPermission(PermissionDictionary::TASK_RESPONSE_EDIT)
			&& !$task->isClosed()
		)
		{
			return true;
		}

		// can edit subordinate's task
		if (
			array_intersect($task->getMembers(RoleDictionary::ROLE_DIRECTOR), $this->user->getAllSubordinates())
		)
		{
			return true;
		}

		$isInDepartment = $task->isInDepartment($this->user->getUserId(), false, [RoleDictionary::ROLE_RESPONSIBLE, RoleDictionary::ROLE_DIRECTOR, RoleDictionary::ROLE_ACCOMPLICE]);

		if (
			$this->user->getPermission(PermissionDictionary::TASK_DEPARTMENT_EDIT)
			&& $isInDepartment
			&& !$task->isClosed()
		)
		{
			return true;
		}

		if (
			$this->user->getPermission(PermissionDictionary::TASK_CLOSED_DEPARTMENT_EDIT)
			&& $isInDepartment
			&& $task->isClosed()
		)
		{
			return true;
		}

		if (
			$this->user->getPermission(PermissionDictionary::TASK_NON_DEPARTMENT_EDIT)
			&& !$isInDepartment
			&& !$task->isClosed()
		)
		{
			return true;
		}

		if (
			$this->user->getPermission(PermissionDictionary::TASK_CLOSED_NON_DEPARTMENT_EDIT)
			&& !$isInDepartment
			&& $task->isClosed()
		)
		{
			return true;
		}

		$this->controller->addError(static::class, 'Access to edit task denied');
		return false;
	}
}