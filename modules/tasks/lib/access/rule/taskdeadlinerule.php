<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\TaskAccessController;

/**
 * @property TaskAccessController $controller
 * @property UserModel $user
 */
class TaskDeadlineRule extends AbstractRule
{
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$item instanceof TaskModel)
		{
			$this->controller->addError(static::class, 'Incorrect task');

			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		if (
			$item->isAllowedChangeDeadline()
			&& $item->isMember($this->user->getUserId(), RoleDictionary::ROLE_RESPONSIBLE)
		)
		{
			return true;
		}

		if (array_intersect($item->getMembers(RoleDictionary::ROLE_DIRECTOR), $this->user->getAllSubordinates()))
		{
			return true;
		}

		if (
			$item->isAllowedChangeDeadline()
			&& array_intersect($item->getMembers(RoleDictionary::ROLE_RESPONSIBLE), $this->user->getAllSubordinates())
		)
		{
			return true;
		}

		return $this->controller->check(ActionDictionary::ACTION_TASK_EDIT, $item, $params);
	}
}
