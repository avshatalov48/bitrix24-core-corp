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

class TaskCreateRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!$task)
		{
			$this->controller->addError(static::class, 'Incorrect task');
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		$groupId = $task->getGroupId();

		// task in group
		if ($groupId)
		{
			return $this->checkGroupPermission($task);
		}

		return true;
	}

	private function checkGroupPermission(AccessibleItem $task): bool
	{
		$group = $task->getGroup();
		if (!$group)
		{
			$this->controller->addError(static::class, 'Unable to load group info');
			return false;
		}

		// tasks disabled for group
		// the group is archived
		if (
			!$group['TASKS_ENABLED']
			|| $group['CLOSED'] === 'Y'
		)
		{
			$this->controller->addError(static::class, 'Unable to create task bc group is closed or tasks disabled');
			return false;
		}

		// default access for group
		if (!Loader::includeModule('socialnetwork'))
		{
			$this->controller->addError(static::class, 'Unable to load socialnetwork');
			return false;
		}

		if (!\Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry::getInstance()->get(
			$task->getGroupId(),
			'tasks',
			'create_tasks',
			$this->user->getUserId()
		))
		{
			$this->controller->addError(static::class, 'Access to create task denied by group permissions');
			return false;
		}

		return true;
	}
}