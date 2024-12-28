<?php

namespace Bitrix\Crm\Agent\Security\Service;

use Bitrix\Crm\Security\Role\Model\EO_Role;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission_Collection;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;

final class RoleSeparateResult
{
	private EO_RolePermission_Collection $permissionsToRemove;

	public function __construct(
		private ?EO_Role $separatedRole = null,
	)
	{
		$this->permissionsToRemove = RolePermissionTable::createCollection();
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

	public function getSeparatedRole(): ?EO_Role
	{
		return $this->separatedRole;
	}

	public function setSeparatedRole(?EO_Role $role): self
	{
		$this->separatedRole = null;

		return $this;
	}

	public function hasSeparatedRole(): bool
	{
		return $this->separatedRole !== null;
	}

	public function hasPermissionsToRemove(): bool
	{
		return !$this->permissionsToRemove->isEmpty();
	}

	public function hasChanges(): bool
	{
		return $this->hasPermissionsToRemove() || $this->hasSeparatedRole();
	}
}
