<?php

namespace Bitrix\HumanResources\Access\Role;

use Bitrix\HumanResources\Model\Access\AccessPermissionTable;
use Bitrix\HumanResources\Model\Access\AccessRoleTable;
use Bitrix\HumanResources\Model\Access\AccessRoleRelationTable;
use Bitrix\HumanResources\Access\Role;


class RoleUtil extends \Bitrix\Main\Access\Role\RoleUtil
{
	protected static function getRoleTableClass(): string
	{
		return AccessRoleTable::class;
	}

	protected static function getRoleRelationTableClass(): string
	{
		return AccessRoleRelationTable::class;
	}

	protected static function getPermissionTableClass(): string
	{
		return AccessPermissionTable::class;
	}

	protected static function getRoleDictionaryClass(): string
	{
		return RoleDictionary::class;
	}

	public static function getDefaultMap(): array
	{
		return [
			RoleDictionary::ROLE_DIRECTOR => (new Role\System\Director())->getMap(),
			RoleDictionary::ROLE_EMPLOYEE => (new Role\System\Employee())->getMap(),
			RoleDictionary::ROLE_ADMIN => (new Role\System\Admin)->getMap(),
		];
	}
}