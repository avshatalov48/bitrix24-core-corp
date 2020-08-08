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
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Rule\Traits\AssignTrait;
use Bitrix\Tasks\Access\Rule\Traits\SubordinateTrait;

class TaskDelegateRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	use AssignTrait, SubordinateTrait;

	/* @var AccessibleItem $oldTask */
	private $oldTask;
	/* @var AccessibleItem $newTask */
	private $newTask;

	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!$task)
		{
			return false;
		}

		if ($task->isClosed())
		{
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		$this->oldTask = $this->newTask = $task;

		if (is_object($params) && $params instanceof TaskModel)
		{
			$this->newTask = $params;
		}

		if (!$this->canDelegate())
		{
			return false;
		}

		foreach ($this->newTask->getMembers(RoleDictionary::ROLE_RESPONSIBLE) as $member)
		{
			if (!$this->canAssignTask($this->oldTask, RoleDictionary::ROLE_RESPONSIBLE, $member, $this->newTask))
			{
				return false;
			}
		}

		return true;
	}

	private function canDelegate()
	{
		if ($this->oldTask->isMember($this->user->getUserId(), RoleDictionary::ROLE_DIRECTOR))
		{
			return true;
		}

		if (
			$this->oldTask->isMember($this->user->getUserId(), RoleDictionary::ROLE_RESPONSIBLE)
			&& $this->user->getPermission(PermissionDictionary::TASK_RESPONSE_DELEGATE)
		)
		{
			return true;
		}

		if ($this->isSubordinateTask($this->oldTask))
		{
			return true;
		}

		if (
			$this->oldTask->getGroupId()
			&& Loader::includeModule("socialnetwork")
			&& \CSocNetFeaturesPerms::CanPerformOperation($this->user->getUserId(), SONET_ENTITY_GROUP, $this->oldTask->getGroupId(), "tasks", "edit_tasks")
		)
		{
			return true;
		}

		return false;
	}
}