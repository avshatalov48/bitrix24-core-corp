<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\Rule\Traits\SubordinateTrait;

class TaskStartRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	use SubordinateTrait;

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
			$task->isMember($this->user->getUserId(), RoleDictionary::ROLE_DIRECTOR)
			|| $task->isMember($this->user->getUserId(), RoleDictionary::ROLE_RESPONSIBLE)
			|| $task->isMember($this->user->getUserId(), RoleDictionary::ROLE_ACCOMPLICE)
		)
		{
			return true;
		}

		if (
			$task->getGroupId()
			&& Loader::includeModule("socialnetwork")
			&& \Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry::getInstance()->get(
				$task->getGroupId(),
				'tasks',
				'edit_tasks',
				$this->user->getUserId()
			)
		)
		{
			return true;
		}

		if ($this->isSubordinateTask($task))
		{
			return true;
		}

		$this->controller->addError(static::class, 'Access to task start denied');
		return false;
	}
}