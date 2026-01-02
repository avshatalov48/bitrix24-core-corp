<?php

namespace Bitrix\HumanResources\Access\Permission;

use Bitrix\Main\Localization\Loc;
use Bitrix\HumanResources\Access\Enum\PermissionValueType;

class PermissionVariablesDictionary
{
	public const VARIABLE_NONE = 0;
	public const VARIABLE_SELF_DEPARTMENTS = 10;
	public const VARIABLE_SELF_TEAMS = 9;
	public const VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS = 20;
	public const VARIABLE_SELF_TEAMS_SUB_TEAMS = 19;
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

	/**
	 * returns variables for permissions with prepared options
	 * @return list<array{id: self::VARIABLE_*, title:string|null, requires?: list<self::VARIABLE_*>, conflictsWith?: list<self::VARIABLE_*>}>
	 */
	public static function getTeamVariables(): array
	{
		return [
			[
				'id' => self::VARIABLE_NONE,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_VARIABLES_NONE_MSGVER_1'),
				'conflictsWith' => [
					self::VARIABLE_SELF_TEAMS,
					self::VARIABLE_SELF_TEAMS_SUB_TEAMS,
					self::VARIABLE_SELF_DEPARTMENTS,
					self::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
				],
			],
			[
				'id' => self::VARIABLE_SELF_TEAMS,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_SELF_TEAMS'),
				'conflictsWith' => [
					self::VARIABLE_NONE,
				],
			],
			[
				'id' => self::VARIABLE_SELF_TEAMS_SUB_TEAMS,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_SELF_TEAMS_SUB_TEAMS'),
				'requires' => [
					self::VARIABLE_SELF_TEAMS,
				],
				'conflictsWith' => [
					self::VARIABLE_NONE,
				],
			],
			[
				'id' => self::VARIABLE_SELF_DEPARTMENTS,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_SELF_DEPARTMENTS'),
				'conflictsWith' => [
					self::VARIABLE_NONE,
				],
			],
			[
				'id' => self::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_SELF_DEPARTMENTS_SUBDEPARTMENTS_MSGVER_1'),
				'requires' => [
					self::VARIABLE_SELF_DEPARTMENTS,
				],
				'conflictsWith' => [
					self::VARIABLE_NONE,
				],
			],
			[
				'id' => self::VARIABLE_ALL,
				'title' => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_VARIABLES_ALL_MSGVER_1'),
				'requires' => [
					self::VARIABLE_SELF_TEAMS,
					self::VARIABLE_SELF_TEAMS_SUB_TEAMS,
					self::VARIABLE_SELF_DEPARTMENTS,
					self::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
				],
				'conflictsWith' => [
					self::VARIABLE_NONE,
				],
			],
		];
	}

	public static function getPermissionValueType(int $value): PermissionValueType
	{
		return match ($value)
		{
			self::VARIABLE_ALL => PermissionValueType::AllValue,
			self::VARIABLE_NONE => PermissionValueType::NoneValue,
			self::VARIABLE_SELF_DEPARTMENTS, self::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS => PermissionValueType::DepartmentValue,
			self::VARIABLE_SELF_TEAMS, self::VARIABLE_SELF_TEAMS_SUB_TEAMS => PermissionValueType::TeamValue,
		};
	}

	public static function getTeamPermissionSelectedVariablesAliases(): array
	{
		$variableIds = [
			self::VARIABLE_SELF_DEPARTMENTS,
			self::VARIABLE_SELF_TEAMS_SUB_TEAMS,
			self::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			self::VARIABLE_ALL,
			self::VARIABLE_SELF_TEAMS,
		];

		sort($variableIds, SORT_STRING);
		$key = implode('|', $variableIds);

		return [
			'selectedVariablesAliases' => ['separator' => '|'] + [$key => Loc::getMessage('HUMAN_RESOURCES_ACCESS_RIGHTS_VARIABLES_ALL_MSGVER_1')],
		];
	}
}