<?php

namespace Bitrix\HumanResources\Access\Role\System;

use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\Role\RoleDictionary;

class Employee extends Base
{
	public function getPermissions(): array
	{
		return [
			PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW => PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CREATE => PermissionVariablesDictionary::VARIABLE_NONE,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_DELETE => PermissionVariablesDictionary::VARIABLE_NONE,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_EDIT => PermissionVariablesDictionary::VARIABLE_NONE,
			PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT => PermissionVariablesDictionary::VARIABLE_NONE,
			PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT => PermissionVariablesDictionary::VARIABLE_NONE,

			PermissionDictionary::HUMAN_RESOURCES_CHAT_BIND_TO_STRUCTURE => PermissionVariablesDictionary::VARIABLE_NONE,
			PermissionDictionary::HUMAN_RESOURCES_CHANEL_BIND_TO_STRUCTURE => PermissionVariablesDictionary::VARIABLE_NONE,
			PermissionDictionary::HUMAN_RESOURCES_CHAT_UNBIND_TO_STRUCTURE => PermissionVariablesDictionary::VARIABLE_NONE,
			PermissionDictionary::HUMAN_RESOURCES_CHANEL_UNBIND_TO_STRUCTURE => PermissionVariablesDictionary::VARIABLE_NONE,
		];
	}

	public function getTitle(): string
	{
		return RoleDictionary::getTitle(RoleDictionary::ROLE_EMPLOYEE);
	}
}