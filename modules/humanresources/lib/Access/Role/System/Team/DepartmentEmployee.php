<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\Role\System\Team;

use Bitrix\HumanResources\Access\Enum\PermissionValueType;
use Bitrix\HumanResources\Access\Permission\Mapper\TeamPermissionMapper;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\Role\System\Base;
use Bitrix\Main\Access\Permission\PermissionDictionary as PermissionDictionaryAlias;

class DepartmentEmployee extends Base
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
			PermissionDictionary::HUMAN_RESOURCES_TEAM_ACCESS_EDIT => PermissionDictionaryAlias::VALUE_NO,
		];
	}
}