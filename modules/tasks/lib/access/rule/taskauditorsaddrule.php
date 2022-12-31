<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Access\AccessibleTask;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\Rule\Traits\AssignTrait;

class TaskAuditorsAddRule extends AbstractRule
{
	use AssignTrait;

	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!$task)
		{
			$this->controller->addError(static::class, 'Incorrect task');
			return false;
		}

		if (!($task instanceof AccessibleTask))
		{
			$this->controller->addError(static::class, 'Incorrect object type');
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		if (!$this->canAssignAuditors($params))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $auditors
	 * @return bool
	 */
	private function canAssignAuditors($auditors): bool
	{
		if (!is_array($auditors))
		{
			$auditors = [$auditors];
		}

		if (empty($auditors))
		{
			return true;
		}

		$currentUser = UserModel::createFromId($this->user->getUserId());
		$currentExtranet = $currentUser->isExtranet();

		foreach ($auditors as $auditorId)
		{
			$auditorId = (int) $auditorId;
			$auditor = UserModel::createFromId($auditorId);

			// always can assign to email users
			if ($auditor->isEmail())
			{
				continue;
			}

			if (
				!$auditor->isExtranet()
				&& !$currentExtranet
			)
			{
				continue;
			}

			if ($currentUser->getUserId() === $auditorId)
			{
				continue;
			}

			if (!$this->isMemberOfUserGroups($currentUser->getUserId(), $auditorId))
			{
				$this->controller->addError(static::class, 'Unable to add auditor from extranet.');
				return false;
			}
		}

		return true;
	}
}