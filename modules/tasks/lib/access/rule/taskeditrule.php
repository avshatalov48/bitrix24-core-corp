<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Rule\Traits\SubordinateTrait;
use Bitrix\Tasks\Access\TaskAccessController;

/**
 * @property TaskAccessController $controller
 * @property UserModel $user
 */
class TaskEditRule extends AbstractRule
{
	use SubordinateTrait;

	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$item instanceof TaskModel)
		{
			$this->controller->addError(static::class, 'Incorrect task');

			return false;
		}

		/** @var TaskModel $item */
		if ($this->user->isAdmin())
		{
			return true;
		}

		if (
			$item->getGroupId() > 0
			&& Loader::includeModule('socialnetwork')
			&& FeaturePermRegistry::getInstance()->get(
				$item->getGroupId(),
				'tasks',
				'edit_tasks',
				$this->user->getUserId()
			)
		)
		{
			return true;
		}

		if (
			!$item->isClosed()
			&& $item->isMember($this->user->getUserId(), RoleDictionary::ROLE_DIRECTOR)
		)
		{
			return true;
		}

		if (
			$item->isClosed()
			&& $this->user->getPermission(PermissionDictionary::TASK_CLOSED_DIRECTOR_EDIT)
			&& $item->isMember($this->user->getUserId(), RoleDictionary::ROLE_DIRECTOR)
		)
		{
			return true;
		}

		if (
			$this->user->getPermission(PermissionDictionary::TASK_ASSIGNEE_EDIT)
			&& !$item->isClosed()
			&& $item->isMember($this->user->getUserId(), RoleDictionary::ROLE_RESPONSIBLE)
		)
		{
			return true;
		}

		// can edit subordinate's task
		if (
			array_intersect($item->getMembers(RoleDictionary::ROLE_DIRECTOR), $this->user->getAllSubordinates())
		)
		{
			return true;
		}

		$isInDepartment = $item->isInDepartment($this->user->getUserId(), false, [RoleDictionary::ROLE_RESPONSIBLE, RoleDictionary::ROLE_DIRECTOR, RoleDictionary::ROLE_ACCOMPLICE]);

		if (
			$isInDepartment
			&& !$item->isClosed()
			&& $this->user->getPermission(PermissionDictionary::TASK_DEPARTMENT_EDIT)

		)
		{
			return true;
		}

		if (
			$isInDepartment
			&& $item->isClosed()
			&& $this->user->getPermission(PermissionDictionary::TASK_CLOSED_DEPARTMENT_EDIT)
		)
		{
			return true;
		}

		if (
			!$isInDepartment
			&& !$item->isClosed()
			&& $this->user->getPermission(PermissionDictionary::TASK_NON_DEPARTMENT_EDIT)
		)
		{
			return true;
		}

		if (
			!$isInDepartment
			&& $item->isClosed()
			&& $this->user->getPermission(PermissionDictionary::TASK_CLOSED_NON_DEPARTMENT_EDIT)
		)
		{
			return true;
		}

		$this->controller->addError(static::class, 'Access to edit task denied');
		$this->controller->addUserError(new Error(Loc::getMessage('TASKS_TASK_EDIT_RULE_EDIT_DENIED')));
		
		return false;
	}
}
