<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Reminder\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\V2\Internal\Access\Reminder\ReminderAccessController;
use Bitrix\Tasks\V2\Internal\Access\Reminder\ReminderModel;

/** @property ReminderAccessController $controller */
class ReminderReadRule extends AbstractRule
{
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$item instanceof ReminderModel)
		{
			$this->controller->addError(static::class, 'Item must be an instance of ReminderModel');

			return false;
		}

		if ($this->user->getUserId() !== $item->getUserId())
		{
			$this->controller->addError(static::class, 'User does not have access to this reminder');

			return false;
		}

		$accessController = TaskAccessController::getInstance($this->user->getUserId());
		if (!$accessController->checkByItemId(ActionDictionary::ACTION_TASK_READ, $item->getTaskId()))
		{
			$this->controller->addError(static::class, 'User does not have access to the task associated with this reminder');

			return false;
		}

		return true;
	}
}