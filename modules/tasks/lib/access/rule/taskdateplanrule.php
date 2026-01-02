<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Role\RoleDictionary;

class TaskDatePlanRule extends AbstractRule
{
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$item instanceof TaskModel)
		{
			$this->controller->addError(static::class, 'Incorrect task');

			return false;
		}

		$task = $item;

		if ($this->user->isAdmin())
		{
			return true;
		}

		if (
			$task->isAllowedChangeDatePlan()
			&& $task->isMember($this->user->getUserId(), RoleDictionary::ROLE_RESPONSIBLE)
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
			$task->isAllowedChangeDatePlan()
			&& array_intersect($task->getMembers(RoleDictionary::ROLE_RESPONSIBLE), $this->user->getAllSubordinates())
		)
		{
			return true;
		}

		return $this->controller->check(ActionDictionary::ACTION_TASK_EDIT, $task, $params);
	}
}
