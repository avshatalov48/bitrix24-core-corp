<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Role\RoleDictionary;

class TaskDeadlineRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
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
			$task->isMember($this->user->getUserId(), RoleDictionary::ROLE_RESPONSIBLE)
			&& $task->isAllowedChangeDeadline()
		)
		{
			return true;
		}

		if (
			array_intersect($task->getMembers(RoleDictionary::ROLE_DIRECTOR), $this->user->getAllSubordinates())
		)
		{
			return true;
		}

		if (
			$task->isAllowedChangeDeadline()
			&& array_intersect($task->getMembers(RoleDictionary::ROLE_RESPONSIBLE), $this->user->getAllSubordinates())
		)
		{
			return true;
		}

		return $this->controller->check(ActionDictionary::ACTION_TASK_EDIT, $task, $params);
	}
}