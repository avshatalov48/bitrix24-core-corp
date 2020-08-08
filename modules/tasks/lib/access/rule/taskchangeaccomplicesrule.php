<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\Rule\Traits\AssignTrait;

class TaskChangeAccomplicesRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	use AssignTrait;

	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!$task)
		{
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		if (!$this->canChangeAccomplices($task))
		{
			return false;
		}

		$this->oldTask = $this->newTask = $task;
		if (is_object($params) && $params instanceof TaskModel)
		{
			$this->newTask = $params;
		}

		foreach ($this->newTask->getMembers(RoleDictionary::ROLE_ACCOMPLICE) as $member)
		{
			if (!$this->canAssignTask($this->oldTask, RoleDictionary::ROLE_ACCOMPLICE, $member, $this->newTask))
			{
				return false;
			}
		}

		return true;
	}

	private function canChangeAccomplices(AccessibleItem $task): bool
	{
		if (!$task->getId())
		{
			return true;
		}

		if (
			$task->isMember($this->user->getUserId(), RoleDictionary::ROLE_DIRECTOR)
		)
		{
			return true;
		}

		if (
			$task->isMember($this->user->getUserId(), RoleDictionary::ROLE_RESPONSIBLE)
			&& $this->user->getPermission(PermissionDictionary::TASK_RESPONSE_ASSIGN)
		)
		{
			return true;
		}

		if (
			$task->getGroupId()
			&& Loader::includeModule("socialnetwork")
			&& \CSocNetFeaturesPerms::CanPerformOperation($this->user->getUserId(), SONET_ENTITY_GROUP, $task->getGroupId(), "tasks", "edit_tasks")
		)
		{
			return true;
		}

		if (array_intersect($task->getMembers(RoleDictionary::ROLE_DIRECTOR), $this->user->getAllSubordinates()))
		{
			return true;
		}

		return false;
	}
}