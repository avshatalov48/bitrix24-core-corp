<?php

namespace Bitrix\Tasks\Access\Model;

use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Tasks\Access\Permission\TasksPermissionTable;
use Bitrix\Tasks\Access\Role\TasksRoleRelationTable;

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

class UserModel extends \Bitrix\Main\Access\User\UserModel
	implements AccessibleUser
{
	private
		$permissions,
		$allSubordinates;

	public function getAllSubordinates(): array
	{
		if ($this->allSubordinates === null)
		{
			$this->allSubordinates = [];
			if ($this->userId === 0)
			{
				return $this->allSubordinates;
			}

			$this->allSubordinates = \Bitrix\Tasks\Integration\Intranet\User::getSubordinate($this->userId, null, true);
		}
		return $this->allSubordinates;
	}

	public function getRoles(): array
	{
		if ($this->roles === null)
		{
			$this->roles = [];
			if ($this->userId === 0 || empty($this->getAccessCodes()))
			{
				return $this->roles;
			}

			$res = TasksRoleRelationTable::query()
				->addSelect('ROLE_ID')
				->whereIn('RELATION', $this->getAccessCodes())
				->exec();
			foreach ($res as $row)
			{
				$this->roles[] = (int) $row['ROLE_ID'];
			}
		}
		return $this->roles;
	}

	public function getPermission(string $permissionId): ?int
	{
		$permissions = $this->getPermissions();
		if (array_key_exists($permissionId, $permissions))
		{
			return $permissions[$permissionId];
		}
		return null;
	}

	private function getPermissions(): array
	{
		if (!$this->permissions)
		{
			$this->permissions = [];
			$roles = $this->getRoles();

			if (empty($roles))
			{
				return $this->permissions;
			}

			$res = TasksPermissionTable::query()
				->addSelect("PERMISSION_ID")
				->addSelect("VALUE")
				->whereIn("ROLE_ID", $roles)
				->exec()
				->fetchAll();

			foreach ($res as $row)
			{
				$permissionId = $row["PERMISSION_ID"];
				$value = (int) $row["VALUE"];
				if (!array_key_exists($permissionId, $this->permissions))
				{
					$this->permissions[$permissionId] = 0;
				}
				if ($value > $this->permissions[$permissionId])
				{
					$this->permissions[$permissionId] = $value;
				}
			}
		}
		return $this->permissions;
	}
}