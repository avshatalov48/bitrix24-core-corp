<?php
/**
 * Bitrix Framework
 *
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Permission;

class PermissionDictionary extends \Bitrix\Main\Access\Permission\PermissionDictionary
{
	public const TASK_ASSIGNEE_EDIT = 1;
	public const TASK_ASSIGNEE_DELEGATE = 2;
	public const TASK_ASSIGNEE_ASSIGN = 3;
	public const TASK_ASSIGNEE_CHECKLIST_EDIT = 4;
	public const TASK_ASSIGNEE_CHECKLIST_ADD = 5;
	public const TASK_CLOSED_DIRECTOR_EDIT = 6;
	public const TASK_DIRECTOR_DELETE = 7;
	public const TASK_ASSIGNEE_CHANGE_RESPONSIBLE = 8;

	public const TASK_DEPARTMENT_DIRECT = 10;
	public const TASK_DEPARTMENT_MANAGER_DIRECT = 11;
	public const TASK_DEPARTMENT_VIEW = 12;
	public const TASK_DEPARTMENT_EDIT = 13;
	public const TASK_CLOSED_DEPARTMENT_EDIT = 14;
	public const TASK_DEPARTMENT_DELETE = 15;

	public const TASK_NON_DEPARTMENT_MANAGER_DIRECT = 20;
	public const TASK_NON_DEPARTMENT_DIRECT = 21;
	public const TASK_NON_DEPARTMENT_VIEW = 22;
	public const TASK_NON_DEPARTMENT_EDIT = 23;
	public const TASK_CLOSED_NON_DEPARTMENT_EDIT = 24;
	public const TASK_NON_DEPARTMENT_DELETE = 25;

	public const TASK_EXPORT = 30;
	public const TASK_IMPORT = 31;

	public const TEMPLATE_CREATE = 40;
	public const TEMPLATE_VIEW = 41;
	public const TEMPLATE_FULL = 42;
	public const TEMPLATE_DEPARTMENT_VIEW = 43;
	public const TEMPLATE_NON_DEPARTMENT_VIEW = 44;
	public const TEMPLATE_DEPARTMENT_EDIT = 45;
	public const TEMPLATE_NON_DEPARTMENT_EDIT = 46;
	public const TEMPLATE_REMOVE = 48;

	public const TASK_ROBOT_EDIT = 47;

	public const TASK_ACCESS_MANAGE = 99;
}