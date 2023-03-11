<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Access\AccessibleItem;

class TaskDisapproveRule extends AbstractRule
{
	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!$this->controller->check(ActionDictionary::ACTION_TASK_APPROVE, $task, $params))
		{
			$this->controller->addError(static::class, 'Access to disapprove task denied');
			return false;
		}

		return true;
	}
}