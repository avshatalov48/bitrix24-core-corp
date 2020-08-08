<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;

class TaskAdminRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if ($this->user->isAdmin())
		{
			return true;
		}

		return (bool) $this->user->getPermission(PermissionDictionary::TASK_ACCESS_MANAGE);
	}
}