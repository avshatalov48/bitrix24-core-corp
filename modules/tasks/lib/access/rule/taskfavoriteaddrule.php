<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;


use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Model\TaskModel;

class TaskFavoriteAddRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!$task || !($task instanceof TaskModel))
		{
			$this->controller->addError(static::class, 'Incorrect task');
			return false;
		}

		if (!$this->controller->check(ActionDictionary::ACTION_TASK_READ, $task))
		{
			$this->controller->addError(static::class, 'Access to read task denied');
			return false;
		}

		return !$task->isFavorite($this->user->getUserId());
	}
}