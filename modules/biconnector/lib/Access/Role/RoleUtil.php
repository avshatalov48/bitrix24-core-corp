<?php

namespace Bitrix\BIConnector\Access\Role;

use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Permission\PermissionTable;
use Bitrix\Main;

final class RoleUtil extends Main\Access\Role\RoleUtil
{
	protected static function getRoleTableClass(): string
	{
		return RoleTable::class;
	}

	protected static function getRoleRelationTableClass(): string
	{
		return RoleRelationTable::class;
	}

	protected static function getPermissionTableClass(): string
	{
		return PermissionTable::class;
	}

	protected static function getRoleDictionaryClass(): ?string
	{
		return RoleDictionary::class;
	}

	/**
	 * Insert data to permission table.
	 *
	 * @param array<int, array{ROLE_ID: int, PERMISSION_ID: int, VALUE: string}> $valuesData
	 *
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function insertPermissions(array $valuesData): void
	{
		if (empty($valuesData))
		{
			return;
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		foreach ($helper->prepareMergeMultiple(PermissionTable::getTableName(), ['ROLE_ID', 'PERMISSION_ID', 'VALUE'], $valuesData) as $sql)
		{
			$connection->query($sql);
		}
	}

	public static function getDashboardPermissions(int $dashboardId): array
	{
		return PermissionTable::getList([
			'select' => ['ROLE_ID', 'PERMISSION_ID', 'VALUE'],
			'filter' => [
				'=VALUE' => $dashboardId,
				'@PERMISSION_ID' => array_values(ActionDictionary::getDashboardPermissionsMap())
			],
		])->fetchAll();
	}
}
