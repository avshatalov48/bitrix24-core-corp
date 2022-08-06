<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;


use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Main\Access\AccessibleItem;

class TaskApproveRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!$task)
		{
			$this->controller->addError(static::class, 'Incorrect task');
			return false;
		}

		if ($task->getStatus() !== \CTasks::STATE_SUPPOSEDLY_COMPLETED)
		{
			$this->controller->addError(static::class, 'Task is already completed');
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

		if (array_intersect($task->getMembers(RoleDictionary::ROLE_DIRECTOR), $this->user->getAllSubordinates()))
		{
			return true;
		}

		$this->controller->addError(static::class, 'Access to approve task denied');
		return false;
	}
}