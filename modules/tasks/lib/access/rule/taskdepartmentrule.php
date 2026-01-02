<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\Rule\Traits\SubordinateTrait;
use Bitrix\Tasks\Access\TaskAccessController;

/**
 * @property TaskAccessController $controller
 */
class TaskDepartmentRule extends AbstractRule
{
	use SubordinateTrait;

	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$item instanceof TaskModel)
		{
			$this->controller->addError(static::class, 'Wrong item type');

			return false;
		}

		// can read subordinate's task
		if ($this->isSubordinateTask($item, false))
		{
			return true;
		}

		$isInDepartment = $item->isInDepartment($this->user->getUserId(), false, [RoleDictionary::ROLE_RESPONSIBLE, RoleDictionary::ROLE_DIRECTOR, RoleDictionary::ROLE_ACCOMPLICE]);

		if (
			$isInDepartment
			&& $this->user->getPermission((string)PermissionDictionary::TASK_DEPARTMENT_VIEW)
		)
		{
			return true;
		}

		if (
			!$isInDepartment
			&& $this->user->getPermission((string)PermissionDictionary::TASK_NON_DEPARTMENT_VIEW)
		)
		{
			return true;
		}

		$this->controller->addError(static::class, 'Access to read task denied');

		return false;
	}
}
