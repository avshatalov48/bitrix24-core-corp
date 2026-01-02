<?php

namespace Bitrix\HumanResources\Access\Role;

use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Model\Access\AccessPermissionTable;
use Bitrix\HumanResources\Model\Access\AccessRoleRelationTable;
use Bitrix\HumanResources\Model\Access\AccessRoleTable;
use Bitrix\Main\Access\Exception\RoleNotFoundException;
use Bitrix\Main\Access\Exception\RoleSaveException;
use Bitrix\Main\Db\SqlQueryException;

class RoleUtil extends \Bitrix\Main\Access\Role\RoleUtil
{
	private const TABLE_NAME = 'b_hr_access_permission';
	private const PRIMARY_KEY = ['ROLE_ID', 'PERMISSION_ID'];
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
			RoleDictionary::ROLE_STRUCTURE_ADMIN => (new Role\System\Admin())->getMap(),
			RoleDictionary::ROLE_HR => (new Role\System\HR())->getMap(),
			RoleDictionary::ROLE_DIRECTOR => (new Role\System\Director())->getMap(),
			RoleDictionary::ROLE_DEPUTY => (new Role\System\Deputy())->getMap(),
			RoleDictionary::ROLE_EMPLOYEE => (new Role\System\Employee())->getMap(),
		];
	}

	public static function getDefaultTeamMap(): array
	{
		return [
			RoleDictionary::ROLE_STRUCTURE_ADMIN => (new Role\System\Team\Admin())->getMap(),
			RoleDictionary::ROLE_TEAM_DIRECTOR => (new Role\System\Team\Director())->getMap(),
			RoleDictionary::ROLE_TEAM_DEPUTY => (new Role\System\Team\Deputy())->getMap(),
			RoleDictionary::ROLE_TEAM_EMPLOYEE => (new Role\System\Team\Employee())->getMap(),
			RoleDictionary::ROLE_DIRECTOR => (new Role\System\Team\DepartmentDirector())->getMap(),
			RoleDictionary::ROLE_DEPUTY => (new Role\System\Team\DepartmentDeputy())->getMap(),
			RoleDictionary::ROLE_EMPLOYEE => (new Role\System\Team\DepartmentEmployee())->getMap(),
		];
	}

	/**
	 * insert data to permission table
	 *
	 * @param array $valuesData
	 *
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function insertPermissions(array $valuesData): void
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		foreach ($helper->prepareMergeMultiple(self::TABLE_NAME, self::PRIMARY_KEY , $valuesData) as $sql)
		{
			$connection->query($sql);
		}
	}

	/**
	 * @param array $permissions
	 *
	 * @return void
	 * @throws RoleNotFoundException
	 * @throws RoleSaveException
	 * @throws SqlQueryException
	 */
	public function updatePermissions(array $permissions): void
	{
		try
		{
			parent::updatePermissions($permissions);
		}
		finally
		{
			AccessPermissionTable::cleanCache();
		}
	}

	/**
	 * @param array $roleRelations
	 *
	 * @return void
	 * @throws \Bitrix\Main\Access\Exception\RoleRelationSaveException
	 */
	public function updateRoleRelations(array $roleRelations): void
	{
		try
		{
			parent::updateRoleRelations($roleRelations);
		}
		finally
		{
			AccessRoleRelationTable::cleanCache();
			AccessRoleTable::cleanCache();
		}
	}
}