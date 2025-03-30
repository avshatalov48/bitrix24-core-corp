<?php

namespace Bitrix\HumanResources\Access\Role\System;

use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

class Director extends Base
{
	public function getPermissions(): array
	{
		$basePermissions = [
			PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW => PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CREATE => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_DELETE => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_EDIT => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,

			PermissionDictionary::HUMAN_RESOURCES_CHAT_BIND_TO_STRUCTURE => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			PermissionDictionary::HUMAN_RESOURCES_CHANEL_BIND_TO_STRUCTURE => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			PermissionDictionary::HUMAN_RESOURCES_CHAT_UNBIND_TO_STRUCTURE => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			PermissionDictionary::HUMAN_RESOURCES_CHANEL_UNBIND_TO_STRUCTURE => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
		];

		if (Loader::includeModule('bitrix24'))
		{
			$basePermissions[PermissionDictionary::HUMAN_RESOURCES_USER_INVITE] = Option::get('bitrix24', 'allow_invite_users', 'N') === 'Y' ? 1 : 0;
		}

		return $basePermissions;
	}
}