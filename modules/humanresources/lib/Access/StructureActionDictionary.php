<?php

namespace Bitrix\HumanResources\Access;

use Bitrix\HumanResources\Access\Permission\PermissionDictionary;

final class StructureActionDictionary
{
	public const ACTION_STRUCTURE_VIEW = 'ACTION_STRUCTURE_VIEW';
	public const ACTION_DEPARTMENT_CREATE = 'ACTION_DEPARTMENT_CREATE';
	public const ACTION_DEPARTMENT_DELETE = 'ACTION_DEPARTMENT_DELETE';
	public const ACTION_DEPARTMENT_EDIT = 'ACTION_DEPARTMENT_EDIT';
	public const ACTION_EMPLOYEE_ADD_TO_DEPARTMENT = 'ACTION_EMPLOYEE_ADD_TO_DEPARTMENT';
	public const ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT = 'ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT';

	public const ACTION_CHAT_BIND_TO_STRUCTURE = 'ACTION_CHAT_BIND_TO_STRUCTURE';
	public const ACTION_CHANEL_BIND_TO_STRUCTURE = 'ACTION_CHANEL_BIND_TO_STRUCTURE';
	public const ACTION_CHAT_UNBIND_TO_STRUCTURE = 'ACTION_CHAT_UNBIND_TO_STRUCTURE';
	public const ACTION_CHANEL_UNBIND_TO_STRUCTURE = 'ACTION_CHANEL_UNBIND_TO_STRUCTURE';

	public const PREFIX = 'ACTION_';

	public static function getActionName(string $actionName): ?string
	{
		$actions = self::getActionPermissionMap();
		if (!array_key_exists($actionName, $actions))
		{
			return null;
		}

		return str_replace(self::PREFIX, '', $actionName);
	}

	public static function getActionPermissionMap(): array
	{
		return [
			self::ACTION_STRUCTURE_VIEW => PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW,

			self::ACTION_DEPARTMENT_CREATE => PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CREATE,
			self::ACTION_DEPARTMENT_DELETE => PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_DELETE,
			self::ACTION_DEPARTMENT_EDIT => PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_EDIT,

			self::ACTION_EMPLOYEE_ADD_TO_DEPARTMENT => PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT,
			self::ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT => PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT,

			self::ACTION_CHAT_BIND_TO_STRUCTURE => PermissionDictionary::HUMAN_RESOURCES_CHAT_BIND_TO_STRUCTURE,
			self::ACTION_CHANEL_BIND_TO_STRUCTURE => PermissionDictionary::HUMAN_RESOURCES_CHANEL_BIND_TO_STRUCTURE,
			self::ACTION_CHAT_UNBIND_TO_STRUCTURE => PermissionDictionary::HUMAN_RESOURCES_CHAT_UNBIND_TO_STRUCTURE,
			self::ACTION_CHANEL_UNBIND_TO_STRUCTURE => PermissionDictionary::HUMAN_RESOURCES_CHANEL_UNBIND_TO_STRUCTURE,
		];
	}
}