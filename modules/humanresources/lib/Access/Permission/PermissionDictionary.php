<?php

namespace Bitrix\HumanResources\Access\Permission;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

class PermissionDictionary extends Main\Access\Permission\PermissionDictionary
{
	public const HUMAN_RESOURCES_USERS_ACCESS_EDIT = '101';

	public const HUMAN_RESOURCES_USER_INVITE = '102';

	public const HUMAN_RESOURCES_STRUCTURE_VIEW = '201';
	public const HUMAN_RESOURCES_DEPARTMENT_CREATE = '202';
	public const HUMAN_RESOURCES_DEPARTMENT_DELETE = '203';
	public const HUMAN_RESOURCES_DEPARTMENT_EDIT = '204';
	public const HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT = '205';
	public const HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT = '206';
	public const HUMAN_RESOURCES_FIRE_EMPLOYEE = '207';
	public const HUMAN_RESOURCES_DEPARTMENT_CHAT_EDIT = '208';
	public const HUMAN_RESOURCES_DEPARTMENT_CHANNEL_EDIT = '209';
	public const HUMAN_RESOURCES_DEPARTMENT_COLLAB_EDIT = '210';
	public const HUMAN_RESOURCES_DEPARTMENT_SETTINGS_EDIT = '211';

	public const HUMAN_RESOURCES_TEAM_VIEW = '301';
	public const HUMAN_RESOURCES_TEAM_CREATE = '302';
	public const HUMAN_RESOURCES_TEAM_DELETE = '303';
	public const HUMAN_RESOURCES_TEAM_EDIT = '304';
	public const HUMAN_RESOURCES_TEAM_MEMBER_ADD = '305';
	public const HUMAN_RESOURCES_TEAM_MEMBER_REMOVE = '306';
	public const HUMAN_RESOURCES_TEAM_SETTINGS_EDIT = '307';
	public const HUMAN_RESOURCES_TEAM_ACCESS_EDIT = '308';
	public const HUMAN_RESOURCES_TEAM_CHAT_EDIT = '309';
	public const HUMAN_RESOURCES_TEAM_CHANNEL_EDIT = '310';
	public const HUMAN_RESOURCES_TEAM_COLLAB_EDIT = '311';

	public static function getHint(int $permissionId): ?string
	{
		$permissionList = self::getList();

		if (!array_key_exists($permissionId, $permissionList))
		{
			return '';
		}

		$rephrasedHintCode = self::getRephrasedHintCode($permissionId);
		return Loc::getMessage($rephrasedHintCode ?? self::HINT_PREFIX . $permissionList[$permissionId]['NAME']) ?? '';
	}

	public static function getTitle($permissionId): string
	{
		$rephrasedPermissionCode = self::getRephrasedPermissionCode($permissionId);
		if ($rephrasedPermissionCode)
		{
			return Loc::getMessage($rephrasedPermissionCode) ?? '';
		}

		return parent::getTitle($permissionId) ?? '';
	}

	public static function getType($permissionId): string
	{
		if (self::isDepartmentVariablesPermission($permissionId))
		{
			return static::TYPE_VARIABLES;
		}

		if (self::isTeamDependentVariablesPermission($permissionId))
		{
			return static::TYPE_DEPENDENT_VARIABLES;
		}

		return parent::getType($permissionId);
	}

	public static function getVariables(int $permissionId): array
	{
		if (self::isTeamDependentVariablesPermission($permissionId))
		{
			return PermissionVariablesDictionary::getTeamVariables();
		}

		return PermissionVariablesDictionary::getVariables();
	}

	private static function getRephrasedPermissionCode(string $permissionId): ?string
	{
		return match ($permissionId) {
			self::HUMAN_RESOURCES_STRUCTURE_VIEW => 'HUMAN_RESOURCES_STRUCTURE_VIEW_MSGVER_2',
			self::HUMAN_RESOURCES_DEPARTMENT_CREATE => 'HUMAN_RESOURCES_DEPARTMENT_CREATE_MSGVER_2',
			self::HUMAN_RESOURCES_DEPARTMENT_DELETE => 'HUMAN_RESOURCES_DEPARTMENT_DELETE_MSGVER_2',
			self::HUMAN_RESOURCES_DEPARTMENT_EDIT => 'HUMAN_RESOURCES_DEPARTMENT_EDIT_MSGVER_2',
			self::HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT => 'HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT_MSGVER_3',
			self::HUMAN_RESOURCES_USERS_ACCESS_EDIT => 'HUMAN_RESOURCES_USERS_ACCESS_EDIT_MSGVER_2',
			self::HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT => 'HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT_MSGVER_1',
			self::HUMAN_RESOURCES_TEAM_SETTINGS_EDIT => 'HUMAN_RESOURCES_TEAM_SETTINGS_EDIT_MSGVER_1',
			self::HUMAN_RESOURCES_USER_INVITE => 'HUMAN_RESOURCES_USER_INVITE_MSGVER_1',
			default => null,
		};
	}

	private static function getRephrasedHintCode(string $permissionId): ?string
	{
		return match ($permissionId) {
			self::HUMAN_RESOURCES_DEPARTMENT_DELETE => 'HINT_HUMAN_RESOURCES_DEPARTMENT_DELETE_MSGVER_3',
			self::HUMAN_RESOURCES_DEPARTMENT_EDIT => 'HINT_HUMAN_RESOURCES_DEPARTMENT_EDIT_MSGVER_3',
			self::HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT => 'HINT_HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT_MSGVER_2',
			self::HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT => 'HINT_HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT_MSGVER_2',
			self::HUMAN_RESOURCES_TEAM_EDIT => 'HINT_HUMAN_RESOURCES_TEAM_EDIT_MSGVER_2',
			self::HUMAN_RESOURCES_TEAM_DELETE => 'HINT_HUMAN_RESOURCES_TEAM_DELETE_MSGVER_1',
			self::HUMAN_RESOURCES_TEAM_MEMBER_ADD => 'HINT_HUMAN_RESOURCES_TEAM_MEMBER_ADD_MSGVER_1',
			self::HUMAN_RESOURCES_TEAM_MEMBER_REMOVE => 'HINT_HUMAN_RESOURCES_TEAM_MEMBER_REMOVE_MSGVER_1',
			self::HUMAN_RESOURCES_TEAM_SETTINGS_EDIT => 'HINT_HUMAN_RESOURCES_TEAM_SETTINGS_EDIT_MSGVER_1',
			default => null,
		};
	}

	/**
	 * @param self::TYPE_* $permissionType
	 */
	public static function getMinValueByTypeOrNull(string|int $permissionType): null|string|int
	{
		return match ($permissionType) {
			self::TYPE_VARIABLES, self::TYPE_DEPENDENT_VARIABLES => PermissionVariablesDictionary::VARIABLE_NONE,
			self::TYPE_TOGGLER => self::VALUE_NO,
			default => null,
		};
	}

	/**
	 * @param self::TYPE_* $permissionType
	 */
	public static function getMaxValueByTypeOrNull(string|int $permissionType): null|string|int
	{
		return match ($permissionType) {
			self::TYPE_VARIABLES, self::TYPE_DEPENDENT_VARIABLES => PermissionVariablesDictionary::VARIABLE_ALL,
			self::TYPE_TOGGLER => self::VALUE_YES,
			default => null,
		};
	}

	public static function isDepartmentVariablesPermission(string $permissionId): bool
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
				self::HUMAN_RESOURCES_USER_INVITE,
				self::HUMAN_RESOURCES_DEPARTMENT_SETTINGS_EDIT,
				self::HUMAN_RESOURCES_DEPARTMENT_CHAT_EDIT,
				self::HUMAN_RESOURCES_DEPARTMENT_CHANNEL_EDIT,
				self::HUMAN_RESOURCES_DEPARTMENT_COLLAB_EDIT,
			],
			true,
		);
	}

	public static function isTeamDependentVariablesPermission(string $permissionId): bool
	{
		return in_array(
			$permissionId,
			[
				self::HUMAN_RESOURCES_TEAM_VIEW,
				self::HUMAN_RESOURCES_TEAM_CREATE,
				self::HUMAN_RESOURCES_TEAM_DELETE,
				self::HUMAN_RESOURCES_TEAM_EDIT,
				self::HUMAN_RESOURCES_TEAM_MEMBER_ADD,
				self::HUMAN_RESOURCES_TEAM_MEMBER_REMOVE,
				self::HUMAN_RESOURCES_TEAM_SETTINGS_EDIT,
				self::HUMAN_RESOURCES_TEAM_CHAT_EDIT,
				self::HUMAN_RESOURCES_TEAM_CHANNEL_EDIT,
				self::HUMAN_RESOURCES_TEAM_COLLAB_EDIT,
			],
			true,
		);
	}

	public static function isTogglePermission(string $permissionId): bool
	{
		return in_array(
			$permissionId,
			[
				self::HUMAN_RESOURCES_USERS_ACCESS_EDIT,
				self::HUMAN_RESOURCES_TEAM_ACCESS_EDIT,
			],
			true,
		);
	}

	public static function isDepartmentCommunicationEditPermission(string $permissionId): bool
	{
		return in_array(
			$permissionId,
			[
				self::HUMAN_RESOURCES_DEPARTMENT_CHAT_EDIT,
				self::HUMAN_RESOURCES_DEPARTMENT_CHANNEL_EDIT,
				self::HUMAN_RESOURCES_DEPARTMENT_COLLAB_EDIT,
			],
			true,
		);
	}

	public static function isTeamCommunicationEditPermission(string $permissionId): bool
	{
		return in_array(
			$permissionId,
			[
				self::HUMAN_RESOURCES_TEAM_CHAT_EDIT,
				self::HUMAN_RESOURCES_TEAM_CHANNEL_EDIT,
				self::HUMAN_RESOURCES_TEAM_COLLAB_EDIT,
			],
			true,
		);
	}
}
