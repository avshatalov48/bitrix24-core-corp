<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Migration\Access\Repository;

use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Flow\Migration\Access\Repository\RoleRepositoryInterface;
use Bitrix\Tasks\Access\Role\TasksRoleTable;
use Bitrix\Tasks\Access\Permission\TasksPermissionTable;

final class RoleRepository implements RoleRepositoryInterface
{
	public function getList(int $startFromId, int $limit): array
	{
		$roleRows =
			TasksRoleTable::query()
				->setSelect(['ID'])
				->where('ID', '>', $startFromId)
				->setLimit($limit)
				->exec()
				->fetchAll();

		$roleRows = array_column($roleRows, 'ID');
		$roleRows = array_map(fn($value) => (int)$value, $roleRows);

		return $roleRows;
	}

	public function setPermissionForRoleId(int $roleId): void
	{
		$permissionRow =
			TasksPermissionTable::query()
				->setSelect(['ID'])
				->where('ROLE_ID', $roleId)
				->where('PERMISSION_ID', PermissionDictionary::FLOW_CREATE)
				->where('VALUE', \Bitrix\Main\Access\Permission\PermissionDictionary::VALUE_YES)
				->exec()
				->fetch();

		if ($permissionRow === false)
		{
			$newPermissionRow = [
				'ROLE_ID' => $roleId,
				'PERMISSION_ID' => PermissionDictionary::FLOW_CREATE,
				'VALUE' => \Bitrix\Main\Access\Permission\PermissionDictionary::VALUE_YES,
			];

			TasksPermissionTable::add($newPermissionRow);
		}
	}
}