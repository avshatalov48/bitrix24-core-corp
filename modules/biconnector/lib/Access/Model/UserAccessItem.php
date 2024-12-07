<?php

namespace Bitrix\BIConnector\Access\Model;

use Bitrix\BIConnector\Access\Permission\PermissionTable;
use Bitrix\BIConnector\Access\Role\RoleRelationTable;
use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\Main;

final class UserAccessItem extends Main\Access\User\UserModel
{
	private ?array $permissions = null;

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
				array_column($relationRows, 'ROLE_ID')
			);
		}

		return $this->roles;
	}

	public function getPermission(string $permissionId): ?int
	{
		$permissions = $this->getPermissions();

		$permissions[$permissionId] = $permissions[$permissionId] ?? null;

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
	 * Returns multiple permission if exists.
	 *
	 * @param string $permissionId String identificatior of permission.
	 *
	 * @return array|null
	 */
	public function getPermissionMulti(string $permissionId): ?array
	{
		if ($this->isAdmin())
		{
			return [PermissionDictionary::VALUE_VARIATION_ALL];
		}

		$permissions = $this->getPermissions();
		$permissions[$permissionId] = $permissions[$permissionId] ?? null;

		return is_array($permissions[$permissionId]) ? $permissions[$permissionId] : null;
	}

	/**
	 * Returns permission list in format ['permission_id' => 1 or 0].
	 *
	 * @return array
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
			->addSelect('PERMISSION_ID')
			->addSelect('VALUE')
			->whereIn('ROLE_ID', $roles)
			->exec()
		;
		while ($permission = $permissions->fetch())
		{
			$permissionId = $permission['PERMISSION_ID'];
			$value = (int)$permission['VALUE'];
			$permissionDescription = PermissionDictionary::getPermission($permissionId);

			if ($permissionDescription['type'] === PermissionDictionary::TYPE_MULTIVARIABLES)
			{
				$this->permissions[$permissionId] = $this->permissions[$permissionId] ?? [];
				if (in_array(PermissionDictionary::VALUE_VARIATION_ALL, $this->permissions[$permissionId], true))
				{
					continue;
				}

				if ($value === PermissionDictionary::VALUE_VARIATION_ALL)
				{
					$this->permissions[$permissionId] = [$value];
				}
				elseif (!in_array($value, $this->permissions[$permissionId], true))
				{
					$this->permissions[$permissionId][] = $value;
				}
			}
			else
			{
				$this->permissions[$permissionId] = $this->permissions[$permissionId] ?? 0;
				if ($value > $this->permissions[$permissionId])
				{
					$this->permissions[$permissionId] = $value;
				}
			}
		}

		return $this->permissions;
	}

	public function canDeleteRestApp(): bool
	{
		$currentUser = Main\Engine\CurrentUser::get();

		return $this->isAdmin() || $currentUser->canDoOperation('bitrix24_config');
	}
}
