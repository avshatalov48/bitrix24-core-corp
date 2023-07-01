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
			$this->controller->addError(static::class, 'Incorrect task');
			return false;
		}

		if (!$this->checkParams($params))
		{
			$this->controller->addError(static::class, 'Incorrect params');
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		// user can update task
		if (!$this->controller->check(ActionDictionary::ACTION_TASK_EDIT, $task, $params))
		{
			$this->controller->addError(static::class, 'Access to edit task denied');
			return false;
		}

		$directors = $params->getMembers(RoleDictionary::ROLE_DIRECTOR);
		if (empty($directors))
		{
			$this->controller->addError(static::class, 'Director is undefined');
			return false;
		}

		if ($directors[0] === $this->user->getUserId())
		{
			return true;
		}

		$this->controller->addError(static::class, 'Access to change director denied');
		return false;
	}

	private function checkParams($params = null): bool
	{
		return is_object($params) && $params instanceof TaskModel;
	}
}