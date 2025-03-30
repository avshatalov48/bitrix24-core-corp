<?php

namespace Bitrix\HumanResources\Access\Rule;

use Bitrix\HumanResources\Access\Model\NodeModel;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Access\Rule\AbstractRule;

final class DepartmentEditRule extends AbstractRule
{
	public function execute(\Bitrix\Main\Access\AccessibleItem $item = null, $params = null): bool
	{
		$permissionValue = $this->user->getPermission(PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_EDIT);
		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_NONE)
		{
			return false;
		}

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_ALL)
		{
			return true;
		}

		if (!($item instanceof NodeModel))
		{
			return false;
		}

		if (!$item->getId())
		{
			return false;
		}

		$accessNodeRepository = Container::getAccessNodeRepository();

		if (
			$permissionValue !== PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS
			&& $permissionValue !== PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS
		)
		{
			return false;
		}

		$checkSubdepartments = $permissionValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS;
		/**
		 * if a department's parent changes,
		 * then necessary to check access to the node, the current parent and the target
		 */
		if (
			!$item->getParentId()
			|| !$item->getTargetId()
			|| $item->getParentId() === $item->getTargetId()
		)
		{
			return $accessNodeRepository->isDepartmentUser($item->getId(), $this->user->getUserId(), $checkSubdepartments);
		}

		return
			$accessNodeRepository->isDepartmentUser($item->getId(), $this->user->getUserId(), $checkSubdepartments)
			&& $accessNodeRepository->isDepartmentUser($item->getParentId(), $this->user->getUserId(), $checkSubdepartments)
			&& $accessNodeRepository->isDepartmentUser($item->getTargetId(), $this->user->getUserId(), $checkSubdepartments)
		;
	}
}