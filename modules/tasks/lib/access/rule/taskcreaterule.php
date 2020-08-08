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
			return false;
		}

		// tasks disabled for group
		// the group is archived
		if (
			!$group->isTasksEnabled()
			|| $group->isArchived()
		)
		{
			return false;
		}

		// default access for group
		return Loader::includeModule('socialnetwork')
			&& \CSocNetFeaturesPerms::CanPerformOperation($this->user->getUserId(), SONET_ENTITY_GROUP, $task->getGroupId(), "tasks", "create_tasks");
	}
}