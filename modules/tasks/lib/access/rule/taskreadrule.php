<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;

/**
 * @property TaskAccessController $controller
 */
class TaskReadRule extends AbstractRule
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

		if ($item->isMember($this->user->getUserId()))
		{
			return true;
		}

		if (
			$item->getGroupId()
			&& Loader::includeModule('socialnetwork')
			&& FeaturePermRegistry::getInstance()->get(
				$item->getGroupId(),
				'tasks',
				'view_all',
				$this->user->getUserId()
			)
		)
		{
			return true;
		}

		if (!$this->controller->check(ActionDictionary::ACTION_TASK_DEPARTMENT, $item, $params))
		{
			$this->controller->addError(static::class, 'Access to read task denied');
			$this->controller->addUserError(new Error(Loc::getMessage('TASKS_TASK_READ_RULE_DENIED')));

			return false;
		}

		return true;
	}
}
