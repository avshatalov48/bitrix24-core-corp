<?php

namespace Bitrix\HumanResources\Access\Model;

use Bitrix\HumanResources\Service\Container;

final class UserModel extends \Bitrix\Main\Access\User\UserModel
{
	private array $permissions = [];

	/**
	 * returns user roles in system
	 * @return array<int>
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

			$this->roles = Container::getAccessRoleRelationRepository()->getRolesByRelationCodes($this->getAccessCodes());
		}
		return $this->roles;
	}

	/**
	 * Returns permission if exists
	 * @param string $permissionId string identification
	 * @return int|null
	 */
	public function getPermission(string $permissionId): ?int
	{
		$permissions = $this->getPermissions();
		if (array_key_exists($permissionId, $permissions))
		{
			return $permissions[$permissionId];
		}
		return null;
	}

	/**
	 * Returns array of permissions with value
	 * @return array<array-key, int>
	 */
	private function getPermissions(): array
	{
		if (!$this->permissions)
		{
			$this->permissions = [];
			$rolesIds = $this->getRoles();

			if (empty($rolesIds))
			{
				return $this->permissions;
			}

			$this->permissions = Container::getAccessPermissionRepository()->getPermissionsByRoleIds($rolesIds);
		}

		return $this->permissions;
	}
}