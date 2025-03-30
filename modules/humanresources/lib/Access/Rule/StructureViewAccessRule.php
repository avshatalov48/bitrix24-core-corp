<?php

namespace Bitrix\HumanResources\Access\Rule;

use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\Main\Access\Rule\AbstractRule;

final class StructureViewAccessRule extends AbstractRule
{
	public function execute(\Bitrix\Main\Access\AccessibleItem $item = null, $params = null): bool
	{
		$permissionValue = $this->user->getPermission(PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW);

		return ($permissionValue && $permissionValue !== PermissionVariablesDictionary::VARIABLE_NONE);
	}
}