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
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Role\RoleDictionary;

class TaskChangeDirectorRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!$task)
		{
			return false;
		}

		if (!$this->checkParams($params))
		{
			return false;
		}

		$this->oldTask = $task;
		$this->newTask = $params;

		if ($this->user->isAdmin())
		{
			return true;
		}

		// user can update task
		if (!$this->controller->check(ActionDictionary::ACTION_TASK_EDIT, $task, $params))
		{
			return false;
		}

		$directors = $this->newTask->getMembers(RoleDictionary::ROLE_DIRECTOR);
		if (empty($directors))
		{
			return false;
		}

		if ($directors[0] === $this->user->getUserId())
		{
			return true;
		}

		return false;
	}

	private function checkParams($params = null): bool
	{
		return is_object($params) && $params instanceof TaskModel;
	}
}