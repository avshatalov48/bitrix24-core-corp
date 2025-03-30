<?php

namespace Bitrix\HumanResources\Access\Role;

use Bitrix\HumanResources\Model\Access\AccessPermissionTable;
use Bitrix\HumanResources\Model\Access\AccessRoleTable;
use Bitrix\HumanResources\Model\Access\AccessRoleRelationTable;
use Bitrix\HumanResources\Access\Role;


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
			RoleDictionary::ROLE_DIRECTOR => (new Role\System\Director())->getMap(),
			RoleDictionary::ROLE_EMPLOYEE => (new Role\System\Employee())->getMap(),
			RoleDictionary::ROLE_ADMIN => (new Role\System\Admin)->getMap(),
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
}