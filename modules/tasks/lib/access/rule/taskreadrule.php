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
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\Rule\Traits\SubordinateTrait;

/**
 * Class TaskReadRule
 * @package Bitrix\Tasks\Access\Rule
 */


class TaskReadRule extends \Bitrix\Main\Access\Rule\AbstractRule
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

		if ($task->isMember($this->user->getUserId()))
		{
			return true;
		}

		if (
			$task->getGroupId()
			&& Loader::includeModule("socialnetwork")
			&& \Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry::getInstance()->get(
				$task->getGroupId(),
				'tasks',
				'view_all',
				$this->user->getUserId()
			)
		)
		{
			return true;
		}

		// can read subordinate's task
		if ($this->isSubordinateTask($task, false))
		{
			return true;
		}

		$isInDepartment = $task->isInDepartment($this->user->getUserId(), false, [RoleDictionary::ROLE_RESPONSIBLE, RoleDictionary::ROLE_DIRECTOR, RoleDictionary::ROLE_ACCOMPLICE]);

		if (
			$this->user->getPermission(PermissionDictionary::TASK_DEPARTMENT_VIEW)
			&& $isInDepartment
		)
		{
			return true;
		}

		if (
			$this->user->getPermission(PermissionDictionary::TASK_NON_DEPARTMENT_VIEW)
			&& !$isInDepartment
		)
		{
			return true;
		}

		$this->controller->addError(static::class, 'Access to read task denied');
		return false;
	}
}
