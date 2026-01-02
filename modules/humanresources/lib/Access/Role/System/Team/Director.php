<?php

namespace Bitrix\HumanResources\Access\Role\System\Team;

use Bitrix\HumanResources\Access\Enum\PermissionValueType;
use Bitrix\HumanResources\Access\Permission\Mapper\TeamPermissionMapper;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\Role\System\Base;
use Bitrix\Main\Access\Permission\PermissionDictionary as PermissionDictionaryAlias;

class Director extends Base
{
	public function getPermissions(): array
	{
		return [
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_VIEW,
				PermissionValueType::TeamValue
			) => PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_VIEW,
				PermissionValueType::DepartmentValue
			) => PermissionVariablesDictionary::VARIABLE_NONE,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_CREATE,
				PermissionValueType::TeamValue
			) => PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_CREATE,
				PermissionValueType::DepartmentValue
			) => PermissionVariablesDictionary::VARIABLE_NONE,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_EDIT,
				PermissionValueType::TeamValue
			) => PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_EDIT,
				PermissionValueType::DepartmentValue
			) => PermissionVariablesDictionary::VARIABLE_NONE,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_DELETE,
				PermissionValueType::TeamValue
			) => PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_DELETE,
				PermissionValueType::DepartmentValue
			) => PermissionVariablesDictionary::VARIABLE_NONE,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_SETTINGS_EDIT,
				PermissionValueType::TeamValue
			) => PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_SETTINGS_EDIT,
				PermissionValueType::DepartmentValue
			) => PermissionVariablesDictionary::VARIABLE_NONE,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_MEMBER_ADD,
				PermissionValueType::TeamValue
			) => PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_MEMBER_ADD,
				PermissionValueType::DepartmentValue
			) => PermissionVariablesDictionary::VARIABLE_NONE,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_MEMBER_REMOVE,
				PermissionValueType::TeamValue
			) => PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_MEMBER_REMOVE,
				PermissionValueType::DepartmentValue
			) => PermissionVariablesDictionary::VARIABLE_NONE,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_CHAT_EDIT,
				PermissionValueType::TeamValue
			) => PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_CHANNEL_EDIT,
				PermissionValueType::TeamValue
			) => PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS,
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_COLLAB_EDIT,
				PermissionValueType::TeamValue
			) => PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS,
			PermissionDictionary::HUMAN_RESOURCES_TEAM_ACCESS_EDIT => PermissionDictionaryAlias::VALUE_NO,
		];
	}
}