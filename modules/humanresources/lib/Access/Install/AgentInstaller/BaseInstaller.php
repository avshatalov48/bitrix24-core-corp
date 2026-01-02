<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\Install\AgentInstaller;

use Bitrix\HumanResources\Access\Install\AccessInstaller;
use Bitrix\HumanResources\Contract\AgentInstaller;
use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Collection\Access\PermissionCollection;
use Bitrix\HumanResources\Repository\Access\PermissionRepository;
use Bitrix\HumanResources\Repository\Access\RoleRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Access\Role;

abstract class BaseInstaller implements AgentInstaller
{
	protected AccessInstaller $accessInstaller;

	public function __construct()
	{
		$this->accessInstaller = new AccessInstaller();
	}

	/**
	 * @throws RoleRelationSaveException
	 * @throws WrongStructureItemException
	 */
	abstract protected function run(): void;

	/**
	 * @return string
	 * @throws RoleRelationSaveException
	 * @throws WrongStructureItemException
	 */
	public function install(): string
	{
		$version = array_flip(InstallerFactory::getVersionMap())[static::class];

		if ($this->accessInstaller->getAccessVersion() > $version)
		{
			return '';
		}

		$this->run();

		$this->accessInstaller->setActualAccessVersion($version);

		return '';
	}

	/**
	 * @param array $roles
	 * @param bool $ignoreExists
	 * @param RoleCategory $category
	 *
	 * @return void
	 * @throws RoleRelationSaveException
	 * @throws WrongStructureItemException
	 */
	protected function fillDefaultSystemPermissions(
		array $roles,
		bool $ignoreExists = false,
		RoleCategory $category = RoleCategory::Department,
	): void
	{
		$roleRepository = new RoleRepository();
		if ($roleRepository->areRolesDefined() && !$ignoreExists)
		{
			return;
		}

		$permissionCollection = new PermissionCollection();
		$permissionRepository = new PermissionRepository();

		foreach ($roles as $roleName => $rolePermissions)
		{
			$role = $roleRepository->create($roleName, $category);
			if (!$role->isSuccess())
			{
				continue;
			}

			$this->installRelation($roleName, $role);

			$roleId = $role->getId();
			foreach ($rolePermissions as $permission)
			{
				$permissionCollection->add(
					new Item\Access\Permission(
						roleId: (int)$roleId,
						permissionId: (string)$permission['id'],
						value: (int)$permission['value'],
					),
				);
			}
		}

		if (!$permissionCollection->empty())
		{
			try
			{
				$permissionRepository->createByCollection($permissionCollection);
			}
			catch (\Exception $e)
			{
				Container::getStructureLogger()->write([
					'entityType' => 'access',
					'message' => $e->getMessage(),
				]);
			}
		}
	}

	/**
	 * @param int|string $roleName
	 * @param \Bitrix\Main\ORM\Data\AddResult $role
	 *
	 * @return void
	 * @throws RoleRelationSaveException
	 */
	protected function installRelation(
		int|string $roleName,
		\Bitrix\Main\ORM\Data\AddResult $role,
	): void
	{
		if ($this->getRelation($roleName))
		{
			$roleUtil = new Role\RoleUtil($role->getId());
			$roleUtil->updateRoleRelations(array_flip([$this->getRelation($roleName)]));
		}
	}

	/**
	 * @param int|string $roleName
	 *
	 * @return string|null
	 */
	protected static function getRelation(int|string $roleName): ?string
	{
		return match ($roleName) {
			Role\RoleDictionary::ROLE_DIRECTOR => AccessCode::ACCESS_DIRECTOR . '0',
			Role\RoleDictionary::ROLE_EMPLOYEE => AccessCode::ACCESS_EMPLOYEE . '0',
			Role\RoleDictionary::ROLE_DEPUTY => AccessCode::ACCESS_DEPUTY . '0',
			default => null,
		};
	}
}