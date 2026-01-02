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
class TaskAttachFileRule extends AbstractRule
{
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$item instanceof TaskModel)
		{
			$this->controller->addError(static::class, 'Incorrect task');

			return false;
		}

		if ($item->isMember($this->user->getUserId()))
		{
			return true;
		}

		if ($this->controller->check(ActionDictionary::ACTION_TASK_EDIT))
		{
			return true;
		}

		$this->controller->addError(static::class, 'No permissions');
		$this->controller->addUserError(new Error(Loc::getMessage('TASKS_TASK_ATTACH_FILE_RULE_NO_PERMISSIONS')));

		return false;
	}
}
