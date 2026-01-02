<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;

/**
 * @property TaskAccessController $controller
 */
class TaskCopyRule extends AbstractRule
{
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$item instanceof TaskModel)
		{
			$this->controller->addError(static::class, 'Incorrect task');

			return false;
		}

		if (!$this->controller->check(ActionDictionary::ACTION_TASK_READ, $item, $params))
		{
			$this->controller->addError(static::class, 'Access to read task denied');
			$this->controller->addUserError(new Error(Loc::getMessage('TASKS_TASK_COPY_RULE_NO_TASK_READ_PERMISSIONS')));

			return false;
		}

		if (!$this->controller->check(ActionDictionary::ACTION_TASK_CREATE))
		{
			$this->controller->addError(static::class, 'Access to create task denied');
			$this->controller->addUserError(new Error(Loc::getMessage('TASKS_TASK_COPY_RULE_NO_TASK_CREATE_PERMISSIONS')));

			return false;
		}

		return true;
	}
}
