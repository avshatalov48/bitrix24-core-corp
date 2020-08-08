<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Install;


use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;

class RoleMap
{

	public static function getDefaultMap()
	{
		return [
			RoleDictionary::TASKS_ROLE_ADMIN => [
				PermissionDictionary::TASK_RESPONSE_EDIT,
				PermissionDictionary::TASK_RESPONSE_DELEGATE,
				PermissionDictionary::TASK_RESPONSE_ASSIGN,
				PermissionDictionary::TASK_RESPONSE_CHECKLIST_EDIT,
				PermissionDictionary::TASK_RESPONSE_CHECKLIST_ADD,
				PermissionDictionary::TASK_CLOSED_DIRECTOR_EDIT,
				PermissionDictionary::TASK_DIRECTOR_DELETE,
				PermissionDictionary::TASK_RESPONSE_CHANGE_RESPONSIBLE,

				PermissionDictionary::TASK_DEPARTMENT_DIRECT,
				PermissionDictionary::TASK_DEPARTMENT_MANAGER_DIRECT,
				PermissionDictionary::TASK_DEPARTMENT_VIEW,
				PermissionDictionary::TASK_DEPARTMENT_EDIT,
				PermissionDictionary::TASK_CLOSED_DEPARTMENT_EDIT,
				PermissionDictionary::TASK_DEPARTMENT_DELETE,

				PermissionDictionary::TASK_NON_DEPARTMENT_MANAGER_DIRECT,
				PermissionDictionary::TASK_NON_DEPARTMENT_DIRECT,
				PermissionDictionary::TASK_NON_DEPARTMENT_VIEW,
				PermissionDictionary::TASK_NON_DEPARTMENT_EDIT,
				PermissionDictionary::TASK_CLOSED_NON_DEPARTMENT_EDIT,
				PermissionDictionary::TASK_NON_DEPARTMENT_DELETE,

				PermissionDictionary::TASK_EXPORT,
				PermissionDictionary::TASK_IMPORT,

				PermissionDictionary::TEMPLATE_CREATE,
				PermissionDictionary::TEMPLATE_VIEW,
				PermissionDictionary::TEMPLATE_FULL,
				PermissionDictionary::TEMPLATE_DEPARTMENT_VIEW,
				PermissionDictionary::TEMPLATE_NON_DEPARTMENT_VIEW,
				PermissionDictionary::TEMPLATE_DEPARTMENT_EDIT,
				PermissionDictionary::TEMPLATE_NON_DEPARTMENT_EDIT,
				PermissionDictionary::TEMPLATE_REMOVE,

				PermissionDictionary::TASK_ROBOT_EDIT
			],
			RoleDictionary::TASKS_ROLE_CHIEF => [
				PermissionDictionary::TASK_RESPONSE_DELEGATE,
				PermissionDictionary::TASK_RESPONSE_CHECKLIST_ADD,
				PermissionDictionary::TASK_CLOSED_DIRECTOR_EDIT,
				PermissionDictionary::TASK_DIRECTOR_DELETE,

				PermissionDictionary::TASK_DEPARTMENT_DIRECT,
				PermissionDictionary::TASK_DEPARTMENT_MANAGER_DIRECT,
				PermissionDictionary::TASK_DEPARTMENT_VIEW,

				PermissionDictionary::TASK_NON_DEPARTMENT_MANAGER_DIRECT,
				PermissionDictionary::TASK_NON_DEPARTMENT_DIRECT,

				PermissionDictionary::TASK_EXPORT,
				PermissionDictionary::TASK_IMPORT,

				PermissionDictionary::TEMPLATE_CREATE,
				PermissionDictionary::TEMPLATE_VIEW,

				PermissionDictionary::TASK_ROBOT_EDIT
			],
			RoleDictionary::TASKS_ROLE_MANAGER => [
				PermissionDictionary::TASK_RESPONSE_DELEGATE,
				PermissionDictionary::TASK_RESPONSE_CHECKLIST_ADD,
				PermissionDictionary::TASK_CLOSED_DIRECTOR_EDIT,
				PermissionDictionary::TASK_DIRECTOR_DELETE,

				PermissionDictionary::TASK_DEPARTMENT_DIRECT,
				PermissionDictionary::TASK_DEPARTMENT_MANAGER_DIRECT,

				PermissionDictionary::TASK_NON_DEPARTMENT_MANAGER_DIRECT,
				PermissionDictionary::TASK_NON_DEPARTMENT_DIRECT,

				PermissionDictionary::TASK_EXPORT,
				PermissionDictionary::TASK_IMPORT,

				PermissionDictionary::TEMPLATE_CREATE,
				PermissionDictionary::TEMPLATE_VIEW,

				PermissionDictionary::TASK_ROBOT_EDIT
			]
		];
	}

}