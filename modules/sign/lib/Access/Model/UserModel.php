<?php

namespace Bitrix\Sign\Access\Model;

use Bitrix\Crm\Security\Role\Model\RoleRelationTable;
use Bitrix\Main;
use Bitrix\Sign\Access\Permission\PermissionTable;
use Bitrix\Intranet;

class UserModel extends Main\Access\User\UserModel
{
	private ?array $permissions = null;
	private ?array $departmentMembers = null;
	private ?array $departmentWithSubsMembers = null;

	/**
	 * get user roles in system
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getRoles(): array
	{
		if ($this->roles === null)
		{
			$this->roles = [];
			if ($this->userId === 0 || empty($this->getAccessCodes()))
			{
				return $this->roles;
			}

			$relationRows = RoleRelationTable::query()
				->addSelect('ROLE_ID')
				->whereIn('RELATION', $this->getAccessCodes())
				->exec()
				->fetchAll()
			;

			$this->roles = array_unique(
				array_column($relationRows, 'ROLE_ID'),
			);
		}

		return $this->roles;
	}

	/**
	 * return permission if exists
	 *
	 * @param string $permissionId string identification
	 *
	 * @return int|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getPermission(string $permissionId): ?int
	{
		$permissions = $this->getPermissions();

		$permissions[$permissionId] ??= null;

		if (is_array($permissions[$permissionId]))
		{
			$permissions[$permissionId] =
				isset($permissions[$permissionId][0])
					? (int)$permissions[$permissionId][0]
					: null
			;
		}

		return $permissions[$permissionId];
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getPermissions(): array
	{
		if ($this->permissions !== null)
		{
			return $this->permissions;
		}
		
		$this->permissions = [];
		$roles = $this->getRoles();
		
		if (empty($roles))
		{
			return $this->permissions;
		}
		
		$query = PermissionTable::query();
		
		$permissions = $query
			->addSelect("PERMISSION_ID")
			->addSelect("VALUE")
			->whereIn("ROLE_ID", $roles)
			->exec()
		;
		while ($permission = $permissions->fetch())
		{
			$permissionId = $permission["PERMISSION_ID"];
			$value = (int)$permission["VALUE"];

			$this->permissions[$permissionId] ??= 0;
			if ($value > $this->permissions[$permissionId])
			{
				$this->permissions[$permissionId] = $value;
			}
		}
		
		return $this->permissions;
	}

	/**
	 * @return array
	 */
	public function getRightGroups(): array
	{
		return \CUser::GetUserGroup($this->userId);
	}

	/**
	 * @return array<int> Return user ids
	 */
	public function getUserDepartmentMembers(bool $withSubs = false): array
	{
		if ($this->departmentMembers !== null && !$withSubs)
		{
			return $this->departmentMembers;
		}
		if ($this->departmentWithSubsMembers !== null && $withSubs)
		{
			return $this->departmentWithSubsMembers;
		}

		$departments = $this->getUserDepartments();
		$res = Intranet\Util::getDepartmentEmployees([
			'DEPARTMENTS' => $departments,
			'RECURSIVE' => !$withSubs ? 'N' : 'Y',
			'ACTIVE' => 'Y',
			'SKIP' => [],
			'SELECT' => ['ID'],
		]);

		if ($withSubs)
		{
			$this->departmentWithSubsMembers = [];
			while ($row = $res->GetNext())
			{
				$this->departmentWithSubsMembers[] = (int)$row['ID'];
			}

			return $this->departmentWithSubsMembers;
		}

		$this->departmentMembers = [];
		while ($row = $res->GetNext())
		{
			$this->departmentMembers[] = (int)$row['ID'];
		}

		return $this->departmentMembers;
	}
}
