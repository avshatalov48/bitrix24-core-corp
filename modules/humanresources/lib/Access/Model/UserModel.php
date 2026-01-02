<?php

namespace Bitrix\HumanResources\Access\Model;

use Bitrix\HumanResources\Access\AuthProvider\StructureAuthProvider;
use Bitrix\HumanResources\Config\Storage;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Loader;
use Bitrix\Main\UserAccessTable;
use Bitrix\Main\UserTable;

final class UserModel extends \Bitrix\Main\Access\User\UserModel implements AccessibleItem
{
	private array $permissions = [];

	/**
	 * AccessibleItem method
	 *
	 * @param int|null $itemId
	 * @return static
	 */
	public static function createFromId(?int $itemId): static
	{
		return parent::createFromId($itemId);
	}

	/**
	 * AccessibleItem method
	 *
	 * @return int
	 */
	public function getId(): int
	{
		return $this->userId ?? 0;
	}

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

	public function getAccessCodes(): array
	{
		if ($this->accessCodes !== null)
		{
			return $this->accessCodes;
		}

		$this->accessCodes = [];
		if ($this->userId === 0)
		{
			return $this->accessCodes;
		}

		$this->accessCodes = array_values(\CAccess::GetUserCodesArray($this->userId) ?? []);

		if (Storage::instance()->isCompanyStructureConverted())
		{
			return $this->accessCodes;
		}

		// add employee access code
		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('intranet'))
		{
			return $this->accessCodes;
		}

		$user = UserTable::getList([
			'select' => ['UF_DEPARTMENT'],
			'filter' => [
				'=ID' => $this->userId,
			],
			'limit' => 1
		])->fetch();

		if (
			$user
			&& is_array($user['UF_DEPARTMENT'])
			&& count($user['UF_DEPARTMENT'])
			&& !empty(array_values($user['UF_DEPARTMENT'])[0])
		)
		{
			$this->accessCodes[] = AccessCode::ACCESS_EMPLOYEE . '0';
		}

		return $this->accessCodes;
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