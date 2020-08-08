<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Model\TemplateModel;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\Rule\Traits\ChecklistTrait;

class ChecklistAddRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	use ChecklistTrait;

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

		if (
			(
				$task->isMember($this->user->getUserId(), RoleDictionary::ROLE_RESPONSIBLE)
				|| $task->isMember($this->user->getUserId(), RoleDictionary::ROLE_ACCOMPLICE)
			)
			&& $this->user->getPermission(PermissionDictionary::TASK_RESPONSE_CHECKLIST_ADD)
		)
		{
			return true;
		}

		return $this->controller->check(ActionDictionary::ACTION_CHECKLIST_EDIT, $task, $params);
	}
}