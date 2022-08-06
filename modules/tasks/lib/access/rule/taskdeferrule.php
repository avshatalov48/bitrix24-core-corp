<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\User\UserSubordinate;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Role\RoleDictionary;

class TaskDeferRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!$task)
		{
			$this->controller->addError(static::class, 'Incorrect task');
			return false;
		}

		if (!in_array($task->getStatus(), [\CTasks::STATE_NEW, \CTasks::STATE_PENDING]))
		{
			$this->controller->addError(static::class, 'Incorrect status');
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		if (
			$task->isMember($this->user->getUserId(), RoleDictionary::ROLE_RESPONSIBLE)
			|| $task->isMember($this->user->getUserId(), RoleDictionary::ROLE_DIRECTOR)
			|| $task->isMember($this->user->getUserId(), RoleDictionary::ROLE_ACCOMPLICE)
		)
		{
			return true;
		}

		return $this->controller->check(ActionDictionary::ACTION_TASK_EDIT, $task);
	}
}