<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;


use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Rule\Traits\SubordinateTrait;

class TaskReminderRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	use SubordinateTrait;

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

		if (!$this->controller->check(ActionDictionary::ACTION_TASK_READ, $task))
		{
			$this->controller->addError(static::class, 'Access to read task denied');
			return false;
		}

		foreach ($params as $reminder)
		{
			if (!array_key_exists('RECEPIENT_TYPE', $reminder))
			{
				$this->controller->addError(static::class, 'Incorrect recipient type');
				return false;
			}
			$target = $reminder['RECEPIENT_TYPE'];

			// can set reminder himself
			if ($target === 'S')
			{
				continue;
			}

			// members can set reminders
			if ($task->isMember($this->user->getUserId()))
			{
				continue;
			}

			// subordinate task
			if ($this->isSubordinateTask($task, false))
			{
				continue;
			}

			$this->controller->addError(static::class, 'Access to remind task denied');
			return false;
		}

		return true;
	}
}