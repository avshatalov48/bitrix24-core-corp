<?php

namespace Bitrix\HumanResources\Access\Rule;

use Bitrix\HumanResources\Access\Model\NodeModel;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Access\Rule\AbstractRule;

final class DepartmentCreateRule extends AbstractRule
{
	public function execute(Main\Access\AccessibleItem $item = null, $params = null): bool
	{
		$permissionValue = $this->user->getPermission(PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CREATE);
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

		if (!$item->getTargetId())
		{
			return false;
		}

		$accessNodeRepository = Container::getAccessNodeRepository();
		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS)
		{
			return $accessNodeRepository->isDepartmentUser($item->getTargetId(), $this->user->getUserId());
		}

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS)
		{
			return $accessNodeRepository->isDepartmentUser($item->getTargetId(), $this->user->getUserId(), checkSubdepartments: true);
		}

		return false;
	}
}