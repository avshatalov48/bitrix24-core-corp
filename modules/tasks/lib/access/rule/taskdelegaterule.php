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

		$this->oldTask = $this->newTask = $task;

		if (is_object($params) && $params instanceof TaskModel)
		{
			$this->newTask = $params;
		}

		if (!$this->canDelegate())
		{
			$this->controller->addError(static::class, 'Access to delegate denied');
			return false;
		}

		if (!$this->canAssignTask($this->oldTask, RoleDictionary::ROLE_RESPONSIBLE, $this->newTask, [$this->user->getUserId()]))
		{
			$this->controller->addError(static::class, 'Access to assign responsible denied');
			return false;
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
			&& \Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry::getInstance()->get(
				$this->oldTask->getGroupId(),
				'tasks',
				'edit_tasks',
				$this->user->getUserId()
			)
		)
		{
			return true;
		}

		return false;
	}
}