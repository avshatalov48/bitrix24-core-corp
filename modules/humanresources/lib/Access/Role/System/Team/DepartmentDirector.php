<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\Role\System\Team;

use Bitrix\HumanResources\Access\Enum\PermissionValueType;
use Bitrix\HumanResources\Access\Permission\Mapper\TeamPermissionMapper;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\Role\System\Base;
use Bitrix\Main\Access\Permission\PermissionDictionary as PermissionDictionaryAlias;

class DepartmentDirector extends Base
{
	public function getPermissions(): array
	{
		return [
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_VIEW,
				PermissionValueType::TeamValue,
			) => PermissionVariablesDictionary::VARIABLE_ALL,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_VIEW,
				PermissionValueType::DepartmentValue,
			) => PermissionVariablesDictionary::VARIABLE_ALL,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_CREATE,
				PermissionValueType::DepartmentValue,
			) => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_EDIT,
				PermissionValueType::DepartmentValue,
			) => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_DELETE,
				PermissionValueType::DepartmentValue,
			) => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_SETTINGS_EDIT,
				PermissionValueType::DepartmentValue,
			) => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_MEMBER_ADD,
				PermissionValueType::DepartmentValue,
			) => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_MEMBER_REMOVE,
				PermissionValueType::DepartmentValue,
			) => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_CHAT_EDIT,
				PermissionValueType::DepartmentValue,
			) => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_CHANNEL_EDIT,
				PermissionValueType::DepartmentValue,
			) => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_COLLAB_EDIT,
				PermissionValueType::DepartmentValue,
			) => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,

			PermissionDictionary::HUMAN_RESOURCES_TEAM_ACCESS_EDIT => PermissionDictionaryAlias::VALUE_YES,
		];
	}
}