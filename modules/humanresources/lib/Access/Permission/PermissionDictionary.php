<?php

namespace Bitrix\HumanResources\Access\Permission;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

class PermissionDictionary extends Main\Access\Permission\PermissionDictionary
{
	public const HUMAN_RESOURCES_USERS_ACCESS_EDIT = 101;

	public const HUMAN_RESOURCES_STRUCTURE_VIEW = 201;
	public const HUMAN_RESOURCES_DEPARTMENT_CREATE = 202;
	public const HUMAN_RESOURCES_DEPARTMENT_DELETE = 203;
	public const HUMAN_RESOURCES_DEPARTMENT_EDIT = 204;
	public const HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT = 205;
	public const HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT = 206;

	public const HUMAN_RESOURCES_CHAT_BIND_TO_STRUCTURE = 301;
	public const HUMAN_RESOURCES_CHANEL_BIND_TO_STRUCTURE = 302;
	public const HUMAN_RESOURCES_CHAT_UNBIND_TO_STRUCTURE = 303;
	public const HUMAN_RESOURCES_CHANEL_UNBIND_TO_STRUCTURE = 304;

	public static function getHint(int $permissionId): string | null
	{
		$permissionList = self::getList();

		if (!array_key_exists($permissionId, $permissionList))
		{
			return '';
		}

		return Loc::getMessage(self::HINT_PREFIX . $permissionList[$permissionId]['NAME']);
	}

	public static function getType($permissionId): string
	{
		return self::isVariable($permissionId)
			? static::TYPE_VARIABLES
			: static::TYPE_TOGGLER
		;
	}

	private static function isVariable($permissionId): bool
	{
		return in_array(
			$permissionId,
			[
				self::HUMAN_RESOURCES_STRUCTURE_VIEW,
				self::HUMAN_RESOURCES_DEPARTMENT_CREATE,
				self::HUMAN_RESOURCES_DEPARTMENT_DELETE,
				self::HUMAN_RESOURCES_DEPARTMENT_EDIT,
				self::HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT,
				self::HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT,
				self::HUMAN_RESOURCES_CHAT_BIND_TO_STRUCTURE,
				self::HUMAN_RESOURCES_CHANEL_BIND_TO_STRUCTURE,
				self::HUMAN_RESOURCES_CHAT_UNBIND_TO_STRUCTURE,
				self::HUMAN_RESOURCES_CHANEL_UNBIND_TO_STRUCTURE,
			],
			true,
		);
	}

	public static function getVariables(): array
	{
		return PermissionVariablesDictionary::getVariables();
	}

	public static function isNodeAccessCheckNeeded(int $permissionId): bool
	{
		return in_array(
			$permissionId,
			[
				self::HUMAN_RESOURCES_STRUCTURE_VIEW,
				self::HUMAN_RESOURCES_DEPARTMENT_DELETE,
				self::HUMAN_RESOURCES_DEPARTMENT_EDIT,
				self::HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT,
				self::HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT,
				self::HUMAN_RESOURCES_CHAT_BIND_TO_STRUCTURE,
				self::HUMAN_RESOURCES_CHANEL_BIND_TO_STRUCTURE,
				self::HUMAN_RESOURCES_CHAT_UNBIND_TO_STRUCTURE,
				self::HUMAN_RESOURCES_CHANEL_UNBIND_TO_STRUCTURE,
			],
			true,
		);
	}

	public static function isParentAccessCheckNeeded(int $permissionId): bool
	{
		return in_array(
			$permissionId,
			[
				self::HUMAN_RESOURCES_DEPARTMENT_CREATE,
				self::HUMAN_RESOURCES_DEPARTMENT_EDIT,
			],
			true,
		);
	}
}
