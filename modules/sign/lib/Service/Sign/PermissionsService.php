<?php

namespace Bitrix\Sign\Service\Sign;

use Bitrix\Main\Loader;
use Bitrix\Main;
use Bitrix\Sign\Access\Permission\PermissionDictionary as CrmPermissionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;
use CCrmPerms;

final class PermissionsService
{
	private ?RolePermissionService $rolePermissionService;

	public function __construct()
	{
		$this->rolePermissionService = Container::instance()->getRolePermissionService();
	}

	/**
	 * @param array<SignPermissionDictionary::*|CrmPermissionDictionary::*, array<RolePermissionService::DEFAULT_ROLE_*, int|CCrmPerms::*>> $permissions
	 * @example \Bitrix\Sign\Agent\Permission\UpdateDefaultTemplatePermissionAgent::run
	 */
	public function updatePermissionsToDefaultRolesIfItsHasEmptyValue(array $permissions): Main\Result
	{
		$result = $this->validateCrmModuleAndPermissionService();
		if (!$result->isSuccess())
		{
			return $result;
		}

		$defaultRoles = $this->rolePermissionService->getDefaultRoles();
		if (empty($defaultRoles))
		{
			return new Main\Result();
		}
		$defaultRolesIds = array_keys($defaultRoles);

		$permissionIds = array_keys($permissions);

		if (!$this->isAllRolesHasEmptyPermissionValue($permissionIds))
		{
			return new Main\Result();
		}

		$savePermissionSettings = [];
		$settings = $this->rolePermissionService->getSettings();
		$roles = $this->rolePermissionService->getRoleList();
		foreach ($settings as $roleId => $currentPermissionSettings)
		{
			if (!in_array($roleId, $defaultRolesIds, true))
			{
				continue;
			}

			$defaultRoleConfig = null;
			foreach ($roles as $role)
			{
				if ((int)$role['ID'] === $roleId)
				{
					$defaultRoleConfig = $role;
				}
			}
			if ($defaultRoleConfig === null)
			{
				continue;
			}

			$accessRights = [];
			foreach ($currentPermissionSettings as $permissionId => ['VALUE' => $oldPermissionValue])
			{
				$accessRights[] = ['id' => $permissionId, 'value' => $oldPermissionValue];
			}
			foreach ($permissions as $permissionId => $defaultRolePermission)
			{
				$newPermission = $defaultRolePermission[$defaultRoleConfig['CODE']] ?? null;
				if ($newPermission === null)
				{
					continue;
				}

				$accessRights[] = ['id' => $permissionId, 'value' => $newPermission];
			}

			$savePermissionSettings[] = [
				'id' => $defaultRoleConfig['ID'],
				'title' => $defaultRoleConfig['NAME'],
				'accessRights' => $accessRights,
			];
		}

		$this->rolePermissionService->saveRolePermissions($savePermissionSettings);

		return new Main\Result();
	}

	/**
	 * @param array<SignPermissionDictionary::*|CrmPermissionDictionary::*, SignPermissionDictionary::*|CrmPermissionDictionary::*> $permissionMap key permission copied from, value permission copied to
	 *
	 * @return Main\Result
	 */
	public function copyPermissionValuesForAllRoles(array $permissionMap): Main\Result
	{
		$result = $this->validateCrmModuleAndPermissionService();
		if (!$result->isSuccess())
		{
			return $result;
		}
		foreach ($permissionMap as $permissionFrom => $permissionTo)
		{
			$permissionFromType = $this->getPermissionType($permissionFrom);
			$permissionToType = $this->getPermissionType($permissionTo);
			if ($permissionFromType !== $permissionToType)
			{
				return Result::createByErrorMessage("Permission types are not equal. Permission from: $permissionFromType, permission to: $permissionToType");
			}
		}

		$roles = $this->rolePermissionService->getRoleList();
		$settings = $this->rolePermissionService->getSettings();

		$savePermissionSettings = [];
		foreach ($roles as $role)
		{
			$roleSettings = $settings[(int)$role['ID']] ?? [];
			$accessRights = [];
			$registeredPermissionIds = [];
			foreach ($permissionMap as $copyFromPermissionId => $copyToPermissionId)
			{
				foreach ($roleSettings as $permissionId => ['VALUE' => $permissionValue])
				{
					if ($permissionId === $copyFromPermissionId)
					{
						$accessRights[] = ['id' => $copyToPermissionId, 'value' => $permissionValue];
						$registeredPermissionIds[] = $copyToPermissionId;
						continue 2;
					}
				}
			}
			foreach ($roleSettings as $permissionId => ['VALUE' => $permissionValue])
			{
				if (in_array($permissionId, $registeredPermissionIds, true))
				{
					continue;
				}

				$accessRights[] = ['id' => $permissionId, 'value' => $permissionValue];
				$registeredPermissionIds[] = $permissionId;
			}

			$savePermissionSettings[] = [
				'id' => $role['ID'],
				'title' => $role['NAME'],
				'accessRights' => $accessRights,
			];
		}

		$this->rolePermissionService->saveRolePermissions($savePermissionSettings);

		return new Main\Result();
	}

	private function isAllRolesHasEmptyPermissionValue(array $permissionIds): bool
	{
		if ($this->rolePermissionService === null)
		{
			return false;
		}

		$previousDefaultPermissionValues = [CCrmPerms::PERM_NONE, '0'];
		$flatAccessRightsFromAllRoles = $this->rolePermissionService->getFlatAccessRightsFromAllRoles();
		foreach ($flatAccessRightsFromAllRoles as $accessRight) {
			$permissionId = $accessRight['id'];
			$permissionValue = $accessRight['value'];
			$permissionValueIsNotDefault = !in_array($permissionValue, $previousDefaultPermissionValues, true);

			if (
				in_array($permissionId, $permissionIds, true)
				&& $permissionValueIsNotDefault
			)
			{
				return false;
			}
		}

		return $this->rolePermissionService->isAllSignPermissionsEmpty();
	}

	private function validateCrmModuleAndPermissionService(): Main\Result
	{
		if (!Loader::includeModule('crm'))
		{
			return Result::createByErrorData('Cant include crm module');
		}

		if ($this->rolePermissionService === null)
		{
			return Result::createByErrorData('Cant get role permission service');
		}

		return new Result();
	}

	private function getPermissionType(string|int $permissionId): string
	{
		if (SignPermissionDictionary::isValid($permissionId))
		{
			return SignPermissionDictionary::getType($permissionId);
		}

		return CrmPermissionDictionary::getType($permissionId);
	}
}
