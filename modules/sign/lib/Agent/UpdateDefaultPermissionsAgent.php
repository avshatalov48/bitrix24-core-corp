<?php

namespace Bitrix\Sign\Agent;

use Bitrix\Main\Loader;
use Bitrix\Sign\Access\Permission\PermissionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Service\Container;
use CCrmPerms;

final class UpdateDefaultPermissionsAgent
{
	private const B2E_SIGN_PERMISSION_IDS = [
		SignPermissionDictionary::SIGN_B2E_PROFILE_FIELDS_READ,
		SignPermissionDictionary::SIGN_B2E_PROFILE_FIELDS_ADD,
		SignPermissionDictionary::SIGN_B2E_PROFILE_FIELDS_EDIT,
		SignPermissionDictionary::SIGN_B2E_PROFILE_FIELDS_DELETE,
		SignPermissionDictionary::SIGN_B2E_MY_SAFE_DOCUMENTS,
		SignPermissionDictionary::SIGN_B2E_MY_SAFE,
		SignPermissionDictionary::SIGN_B2E_TEMPLATES,
	];
	private const B2E_CRM_PERMISSION_IDS = [
		PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_ADD,
		PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_WRITE,
		PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_READ,
		PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_DELETE,
	];

	public static function run(): string
	{
		if (!Loader::includeModule('crm'))
		{
			return '';
		}

		$logger = Logger::getInstance();
		$rolePermissionService = Container::instance()->getRolePermissionService();
		if ($rolePermissionService === null)
		{
			$logger->notice('RolePermissionService not found. Cant update default permissions');
			return '';
		}

		$isDefaultRolesExists = self::isDefaultRolesExists($rolePermissionService);
		if (!$isDefaultRolesExists)
		{
			$logger->notice('Default roles doesnt exists');
			return '';
		}

		$allPermissionsHasDefaultValues = self::isAllPermissionsHasDefaultValues($rolePermissionService);
		if (!$allPermissionsHasDefaultValues)
		{
			$logger->notice('All permissions has default values');
			return '';
		}

		self::updateDefaultPermissions($rolePermissionService);

		return '';
	}

	private static function isDefaultRolesExists(RolePermissionService $rolePermissionService): bool
	{
		$defaultRoles = $rolePermissionService->getDefaultRoles();

		return !empty($defaultRoles);
	}

	private static function isAllPermissionsHasDefaultValues(RolePermissionService $rolePermissionService): bool
	{
		$flatAccessRightsFromAllRoles = $rolePermissionService->getFlatAccessRightsFromAllRoles();

		$permissionIds = [...self::B2E_CRM_PERMISSION_IDS, ...self::B2E_SIGN_PERMISSION_IDS];
		$previousDefaultPermissionValues = [\CCrmPerms::PERM_NONE, '0'];
		foreach ($flatAccessRightsFromAllRoles as $accessRight)
		{
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

		return true;
	}

	private static function updateDefaultPermissions(RolePermissionService $rolePermissionService): void
	{
		$rolesToInstall = self::getInstallingRoles($rolePermissionService);
		if (empty($rolesToInstall))
		{
			return;
		}

		$roles = $rolePermissionService->getRoleList();

		foreach ($roles as $role)
		{
			foreach ($rolesToInstall as $roleToInstall => $permission)
			{
				if ($role['CODE'] === $roleToInstall)
				{
					$permission[0]['id'] = $role['ID'];
					$permission[0]['title'] = $role['NAME'];
					$rolePermissionService->saveRolePermissions($permission);
				}
			}
		}
	}

	private static function getInstallingRoles(RolePermissionService $rolePermissionService): array
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$settings = $rolePermissionService->getSettings();
		$defaultRoles = $rolePermissionService->getDefaultRoles();

		$employeeRoleId = null;
		$chiefRoleId = null;
		foreach ($defaultRoles as $defaultRoleId => $defaultRoleData)
		{
			if ($defaultRoleData['CODE'] === $rolePermissionService::DEFAULT_ROLE_EMPLOYEE_CODE)
			{
				$employeeRoleId = $defaultRoleId;
			}
			elseif ($defaultRoleData['CODE'] === $rolePermissionService::DEFAULT_ROLE_CHIEF_CODE)
			{
				$chiefRoleId = $defaultRoleId;
			}
		}

		$result = [];
		if ($employeeRoleId !== null)
		{
			$employeeAccessRights = [
				[
					'id' => PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_ADD,
					'value' => CCrmPerms::PERM_SELF,
				],
				[
					'id' => PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_READ,
					'value' => CCrmPerms::PERM_SELF,
				],
				[
					'id' => PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_WRITE,
					'value' => CCrmPerms::PERM_SELF,
				],
				[
					'id' => PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_DELETE,
					'value' => CCrmPerms::PERM_SELF,
				],
				[
					'id' => SignPermissionDictionary::SIGN_B2E_MY_SAFE,
					'value' => 1,
				],
				[
					'id' => SignPermissionDictionary::SIGN_B2E_MY_SAFE_DOCUMENTS,
					'value' => CCrmPerms::PERM_SELF,
				],
				[
					'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATES,
					'value' => CCrmPerms::PERM_SELF,
				],
			];
			$existedChiefPermissions = $settings[$employeeRoleId];
			$b2eUpdatedPermissionIds = array_column($employeeAccessRights, 'id');
			foreach ($existedChiefPermissions as $existedEmployeePermissionName => $existedEmployeePermissionData)
			{
				if (!in_array($existedEmployeePermissionName, $b2eUpdatedPermissionIds, true))
				{
					$employeeAccessRights[] = [
						'id' => $existedEmployeePermissionName,
						'value' => $existedEmployeePermissionData['VALUE'],
					];
				}
			}

			$result[$rolePermissionService::DEFAULT_ROLE_EMPLOYEE_CODE] = [
				[
					'accessRights' => $employeeAccessRights,
				],
			];
		}
		if ($chiefRoleId !== null)
		{
			$chiefAccessRights = [
				[
					'id' => PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_ADD,
					'value' => CCrmPerms::PERM_SUBDEPARTMENT,
				],
				[
					'id' => PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_READ,
					'value' => CCrmPerms::PERM_SUBDEPARTMENT,
				],
				[
					'id' => PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_WRITE,
					'value' => CCrmPerms::PERM_SUBDEPARTMENT,
				],
				[
					'id' => PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_DELETE,
					'value' => CCrmPerms::PERM_SUBDEPARTMENT,
				],
				[
					'id' => SignPermissionDictionary::SIGN_B2E_MY_SAFE,
					'value' => 1,
				],
				[
					'id' => SignPermissionDictionary::SIGN_B2E_MY_SAFE_DOCUMENTS,
					'value' => CCrmPerms::PERM_SUBDEPARTMENT,
				],
				[
					'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATES,
					'value' => CCrmPerms::PERM_SUBDEPARTMENT,
				],
			];

			$existedChiefPermissions = $settings[$chiefRoleId];
			$b2eUpdatedPermissionIds = array_column($chiefAccessRights, 'id');
			foreach ($existedChiefPermissions as $existedChiefPermissionName => $existedChiefPermissionData)
			{
				if (!in_array($existedChiefPermissionName, $b2eUpdatedPermissionIds, true))
				{
					$chiefAccessRights[] = [
						'id' => $existedChiefPermissionName,
						'value' => $existedChiefPermissionData['VALUE'],
					];
				}
			}

			$result[$rolePermissionService::DEFAULT_ROLE_CHIEF_CODE] = [
				[
					'accessRights' => $chiefAccessRights,
				],
			];
		}

		return $result;
	}
}