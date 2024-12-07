<?php

namespace Bitrix\HumanResources\Access\Permission;

use Bitrix\Main\Localization\Loc;

class PermissionVariablesDictionary
{
	public const VARIABLE_NONE = 1;
	public const VARIABLE_ALL = 2;
	public const VARIABLE_SELF_DEPARTMENTS = 3;
	public const VARIABLE_SELF_DEPARTMENTS_SUBDEPARTMENTS = 4;

	/**
	 * returns variables for permissions with prepared options
	 * @return list<array{id: self::VARIABLE_*, title:string|null}>
	 */
	public static function getVariables(): array
	{
		return [
			[
				'id' => self::VARIABLE_ALL,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_VARIABLES_ALL'),
			],
			[
				'id' => self::VARIABLE_SELF_DEPARTMENTS_SUBDEPARTMENTS,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_SELF_DEPARTMENTS_SUBDEPARTMENTS'),
			],
			[
				'id' => self::VARIABLE_SELF_DEPARTMENTS,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_SELF_DEPARTMENTS'),
			],
			[
				'id' => self::VARIABLE_NONE,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_VARIABLES_NONE'),
			],
		];
	}
}