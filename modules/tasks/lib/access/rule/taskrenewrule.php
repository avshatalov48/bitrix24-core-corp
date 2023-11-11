<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;


use Bitrix\Main\Access\User\UserSubordinate;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Internals\Task\Status;

class TaskRenewRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!$task)
		{
			$this->controller->addError(static::class, 'Incorrect task');
			return false;
		}

		$status = (int)$task->getStatus();
		$isDirector = $task->isMember($this->user->getUserId(), RoleDictionary::ROLE_DIRECTOR);
		$isResponsible = $task->isMember($this->user->getUserId(), RoleDictionary::ROLE_RESPONSIBLE);
		$isAccomplice = $task->isMember($this->user->getUserId(), RoleDictionary::ROLE_ACCOMPLICE);

		if (
			$status === Status::SUPPOSEDLY_COMPLETED
			&& !$isDirector
			&& ($isResponsible || $isAccomplice)
		)
		{
			return true;
		}

		if (!in_array($status, [Status::COMPLETED, Status::DEFERRED], true))
		{
			$this->controller->addError(static::class, 'Incorrect status');
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		if ($isDirector || $isResponsible || $isAccomplice)
		{
			return true;
		}

		return $this->controller->check(ActionDictionary::ACTION_TASK_EDIT, $task, $params);
	}
}