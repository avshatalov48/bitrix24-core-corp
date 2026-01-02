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
class TaskDetachFileRule extends AbstractRule
{
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$item instanceof TaskModel)
		{
			$this->controller->addError(static::class, 'Incorrect task');

			return false;
		}

		if ($this->controller->check(ActionDictionary::ACTION_TASK_EDIT))
		{
			return true;
		}

		if (!is_array($params) || !isset($params['attachments']) || !is_array($params['attachments']))
		{
			$this->controller->addError(static::class, 'Incorrect parameters');

			return false;
		}

		if (!$item->isMember($this->user->getUserId()))
		{
			$this->controller->addError(static::class, 'Incorrect attachment data');

			$this->controller->addUserError(new Error(Loc::getMessage('TASKS_TASK_DETACH_FILE_RULE_NO_PERMISSIONS')));

			return false;
		}

		$attachments = $params['attachments'];
		foreach ($attachments as $attachment)
		{
			if (
				!is_array($attachment)
				|| !isset($attachment['owner']['id'])
				|| (int)$attachment['owner']['id'] !== $this->user->getUserId()
			)
			{
				$this->controller->addError(static::class, 'Incorrect attachment data');

				$this->controller->addUserError(new Error(Loc::getMessage('TASKS_TASK_DETACH_FILE_RULE_NO_PERMISSIONS')));

				return false;
			}
		}

		return true;
	}
}
