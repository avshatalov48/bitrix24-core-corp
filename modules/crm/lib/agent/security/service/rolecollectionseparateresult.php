<?php

namespace Bitrix\Crm\Agent\Security\Service;

use Bitrix\Crm\Security\Role\Model\EO_Role;
use Bitrix\Crm\Security\Role\Model\EO_Role_Collection;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission_Collection;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;
use Bitrix\Crm\Security\Role\Model\RoleTable;

final class RoleCollectionSeparateResult
{
	private EO_Role_Collection $separatedRoles;
	private EO_Role_Collection $changedRoles;
	private EO_RolePermission_Collection $permissionsToRemove;

	public function __construct()
	{
		$this->separatedRoles = RoleTable::createCollection();
		$this->changedRoles = RoleTable::createCollection();
		$this->permissionsToRemove = RolePermissionTable::createCollection();
	}

	public function getSeparatedRoles(): EO_Role_Collection
	{
		return $this->separatedRoles;
	}

	public function addSeparatedRole(?EO_Role $role): self
	{
		if ($role === null)
		{
			return $this;
		}

		$this->separatedRoles->add($role);

		return $this;
	}

	public function getChangedRoles(): EO_Role_Collection
	{
		return $this->changedRoles;
	}

	public function addChangedRole(?EO_Role $role): self
	{
		if ($role === null)
		{
			return $this;
		}

		$this->changedRoles->add($role);

		return $this;
	}

	public function getPermissionsToRemove(): EO_RolePermission_Collection
	{
		return $this->permissionsToRemove;
	}

	public function addPermissionToRemove(EO_RolePermission $permission): self
	{
		$this->permissionsToRemove->add($permission);

		return $this;
	}

	public function addPermissionsToRemove(EO_RolePermission_Collection $permissionCollection): self
	{
		foreach ($permissionCollection as $permission)
		{
			$this->addPermissionToRemove($permission);
		}

		return $this;
	}
}
