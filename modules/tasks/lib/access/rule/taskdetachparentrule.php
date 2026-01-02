<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;

/**
 * @property TaskAccessController $controller
 */
class TaskDetachParentRule extends AbstractRule
{
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$item instanceof TaskModel)
		{
			$this->controller->addError(static::class, 'Incorrect task');

			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		$parentId = $item->getParentId();
		if ($parentId <= 0)
		{
			return true;
		}

		if (!$this->controller->checkByItemId(ActionDictionary::ACTION_TASK_READ, $parentId))
		{
			$this->controller->addError(static::class, 'Access to parent task denied');

			return false;
		}

		if (!$this->controller->checkByItemId(ActionDictionary::ACTION_TASK_EDIT, $item->getId()))
		{
			$this->controller->addError(static::class, 'Access to detach parent task denied');

			return false;
		}

		return true;
	}
}
