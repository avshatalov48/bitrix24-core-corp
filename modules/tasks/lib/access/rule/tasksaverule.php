<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\Rule\Traits\AssignTrait;
use Bitrix\Tasks\Access\Rule\Traits\GroupTrait;

class TaskSaveRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	use AssignTrait;
	use GroupTrait;

	/* @var AccessibleItem $oldTask */
	private $oldTask;
	/* @var AccessibleItem $newTask */
	private $newTask;

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

		$this->oldTask = $task;
		$this->newTask = $params;

		// the task should be in the group and tasks enabled on this group
		if (
			$this->newTask->getGroup()
			&& !$this->newTask->getGroup()['TASKS_ENABLED']
		)
		{
			$this->controller->addError(static::class, 'Tasks are disabled in group');
			return false;
		}

		// user is admin
		if ($this->user->isAdmin())
		{
			return true;
		}

		// user can update task
		if (!$this->canUpdateTask())
		{
			$this->controller->addError(static::class, 'Access to create or update task denied');
			return false;
		}

		// user can set group
		if (
			$this->newTask->getGroupId()
			&& $this->newTask->getGroupId() !== $this->oldTask->getGroupId()
			&& !$this->canSetGroup($this->user->getUserId(), $this->newTask->getGroupId())
		)
		{
			$this->controller->addError(static::class, 'Access to set group denied');
			return false;
		}

		// user can assign task to this man
		if (!$this->canAssignTask($this->oldTask, RoleDictionary::ROLE_RESPONSIBLE, $this->newTask))
		{
			$this->controller->addError(static::class, 'Access to assign responsible denied');
			return false;
		}

		// user can assign task to co-executors
		if (!$this->canAssignTask($this->oldTask, RoleDictionary::ROLE_ACCOMPLICE, $this->newTask))
		{
			$this->controller->addError(static::class, 'Access to assign accomplice denied');
			return false;
		}

		// user can assign task to auditors
		if (!$this->canAssignAuditors())
		{
			return false;
		}

		// user can change director (if director has been changed)
		if (
			$this->changedDirector()
			&& !in_array($this->user->getUserId(), $this->newTask->getMembers(RoleDictionary::ROLE_RESPONSIBLE))
			&& !$this->controller->check(ActionDictionary::ACTION_TASK_CHANGE_DIRECTOR, $task, $params)
		)
		{
			$this->controller->addError(static::class, 'Access to assign director denied');
			return false;
		}

		return true;
	}

	private function changedDirector()
	{
		$directors = $this->newTask->getMembers(RoleDictionary::ROLE_DIRECTOR);
		if (empty($directors))
		{
			return false;
		}

		if ($directors[0] === $this->user->getUserId())
		{
			return false;
		}

		$responsibles = $this->newTask->getMembers(RoleDictionary::ROLE_RESPONSIBLE);

		// new task
		if (
			!$this->oldTask->getId()
			&& count($responsibles) === 1
			&& $responsibles[0] === $this->user->getUserId()
		)
		{
			return false;
		}

		// director hasn't changed
		if (
			$this->oldTask->getId()
			&& !empty($this->oldTask->getMembers(RoleDictionary::ROLE_DIRECTOR))
			&& $directors[0] === $this->oldTask->getMembers(RoleDictionary::ROLE_DIRECTOR)[0]
		)
		{
			return false;
		}

		return true;
	}

	private function canUpdateTask()
	{
		// can create new task
		if (
			$this->isNew()
		)
		{
			return $this->controller->check(ActionDictionary::ACTION_TASK_CREATE, $this->newTask);
		}

		return $this->controller->check(ActionDictionary::ACTION_TASK_EDIT, $this->oldTask);
	}

	private function checkParams($params = null): bool
	{
		return is_object($params) && $params instanceof TaskModel;
	}

	private function isNew(): bool
	{
		return !$this->oldTask->getId();
	}

	private function canAssignAuditors(): bool
	{
		if ($this->isNew())
		{
			$auditors = $this->newTask->getMembers(RoleDictionary::ROLE_AUDITOR);

			return $this->controller->check(ActionDictionary::ACTION_TASK_AUDITORS_ADD, $this->newTask, $auditors);
		}

		$auditors = array_diff(
			$this->newTask->getMembers(RoleDictionary::ROLE_AUDITOR),
			$this->oldTask->getMembers(RoleDictionary::ROLE_AUDITOR),
		);

		return $this->controller->check(ActionDictionary::ACTION_TASK_AUDITORS_ADD, $this->oldTask, $auditors);
	}
}