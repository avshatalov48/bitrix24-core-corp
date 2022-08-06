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

class ChecklistToggleRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	use ChecklistTrait;

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

		if ($task instanceof TemplateModel)
		{
			return $this->controller->check(ActionDictionary::ACTION_CHECKLIST_EDIT, $task, $params);
		}

		if (!$this->controller->check(ActionDictionary::ACTION_TASK_READ, $task, $params))
		{
			return false;
		}

		$checklist = $this->getModelFromParams($params);
		if ($checklist->getEntityId() !== $task->getId())
		{
			$this->controller->addError(static::class, 'Incorrect checklist');
			return false;
		}

		if (
			$task->isMember($this->user->getUserId(), RoleDictionary::ROLE_DIRECTOR)
			|| $task->isMember($this->user->getUserId(), RoleDictionary::ROLE_RESPONSIBLE)
			|| $task->isMember($this->user->getUserId(), RoleDictionary::ROLE_ACCOMPLICE)
		)
		{
			return true;
		}

		return $this->controller->check(ActionDictionary::ACTION_CHECKLIST_EDIT, $task, $params);
	}
}