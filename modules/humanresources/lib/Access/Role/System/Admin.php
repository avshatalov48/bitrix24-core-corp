<?php

namespace Bitrix\HumanResources\Access\Role\System;

use Bitrix\HumanResources\Access\Enum\PermissionValueType;
use Bitrix\HumanResources\Access\Permission\Mapper\TeamPermissionMapper;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\Main\Access\Permission\PermissionDictionary as PermissionDictionaryAlias;

class Admin extends Base
{
	public function getPermissions(): array
	{
		return [
			PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW => PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CREATE => PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_DELETE => PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_EDIT => PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT => PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT => PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionDictionary::HUMAN_RESOURCES_USER_INVITE => PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_SETTINGS_EDIT => PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CHAT_EDIT => PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CHANNEL_EDIT => PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_COLLAB_EDIT => PermissionVariablesDictionary::VARIABLE_ALL,

			PermissionDictionary::HUMAN_RESOURCES_USERS_ACCESS_EDIT => 1,
			PermissionDictionary::HUMAN_RESOURCES_FIRE_EMPLOYEE => 1,
		];
	}
}