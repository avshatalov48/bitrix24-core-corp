<?php

namespace Bitrix\HumanResources\Access\Permission;

use Bitrix\Main\Localization\Loc;

class PermissionVariablesDictionary
{
	public const VARIABLE_NONE = 0;
	public const VARIABLE_SELF_DEPARTMENTS = 10;
	public const VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS = 20;
	public const VARIABLE_ALL = 30;


	/**
	 * returns variables for permissions with prepared options
	 * @return list<array{id: self::VARIABLE_*, title:string|null}>
	 */
	public static function getVariables(): array
	{
		return [
			[
				'id' => self::VARIABLE_ALL,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_VARIABLES_ALL_MSGVER_1'),
			],
			[
				'id' => self::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_SELF_DEPARTMENTS_SUBDEPARTMENTS_MSGVER_1'),
			],
			[
				'id' => self::VARIABLE_SELF_DEPARTMENTS,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_SELF_DEPARTMENTS'),
			],
			[
				'id' => self::VARIABLE_NONE,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_VARIABLES_NONE_MSGVER_1'),
			],
		];
	}
}