<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;

class TaskCompleteResultRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!$task)
		{
			$this->controller->addError(static::class, 'Incorrect task');
			return false;
		}

		if ($task->isClosed())
		{
			$this->controller->addError(static::class, 'Task already completed');
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		if ($task->isMember($this->user->getUserId(), RoleDictionary::ROLE_DIRECTOR))
		{
			return true;
		}

		$lastResult = ResultManager::getLastResult($task->getId());

		if (
			ResultManager::requireResult($task->getId())
			&& (
				!$lastResult
				|| (int) $lastResult['STATUS'] !== ResultTable::STATUS_OPENED
			)
		)
		{
			$this->controller->addError(static::class, 'Unable to complete task without result');
			return false;
		}

		return true;
	}
}