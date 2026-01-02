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
class TaskChangeGanttDependenceRule extends AbstractRule
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

		$dependentId = (int)($params['dependentId'] ?? 0);
		if ($dependentId <= 0)
		{
			return true;
		}

		if (!$this->controller->checkByItemId(ActionDictionary::ACTION_TASK_READ, $item->getId()))
		{
			$this->controller->addError(static::class, 'Access to read task denied');

			return false;
		}

		if (!$this->controller->checkByItemId(ActionDictionary::ACTION_TASK_READ, $dependentId))
		{
			$this->controller->addError(static::class, 'Access to dependent task denied');

			return false;
		}

		if (!$this->controller->checkByItemId(ActionDictionary::ACTION_TASK_DEADLINE, $item->getId()))
		{
			$this->controller->addError(static::class, 'Access to set deadline for task denied');
			$this->controller->clearUserErrors();
			$this->controller->addUserError(new Error(Loc::getMessage('TASKS_TASK_CHANGE_GANTT_DEPENDENCE_RULE_DEADLINE_DENIED')));

			return false;
		}

		if (!$this->controller->checkByItemId(ActionDictionary::ACTION_TASK_DEADLINE, $dependentId))
		{
			$this->controller->addError(static::class, 'Access to set deadline for dependent task denied');
			$this->controller->clearUserErrors();
			$this->controller->addUserError(new Error(Loc::getMessage('TASKS_TASK_CHANGE_GANTT_DEPENDENCE_RULE_DEADLINE_DENIED')));

			return false;
		}

		return true;
	}
}
