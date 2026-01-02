<?php

namespace Bitrix\Sign\Access\Install;

use Bitrix\Crm\Integration\Sign\Access\Service\RolePermissionService;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Sign\Access\Permission\PermissionTable;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService as SignRolePermissionService;
use CCrmRole;
use Bitrix\Crm\Service\UserPermissions;

class AccessInstaller
{
	public static function install($removeAllPrevious = false): string
	{
		try
		{
			if (!Loader::includeModule('crm'))
			{
				return '';
			}
		}
		catch (LoaderException $e)
		{
			return '';
		}

		if ($removeAllPrevious)
		{
			PermissionTable::deleteList(['>ID' => 0]);
		}
		$roles = CCrmRole::GetList(
			['ID' => 'DESC'],
			['=GROUP_CODE' => RolePermissionService::ROLE_GROUP_CODE],
		);

		$rolesToInstall = [
			SignRolePermissionService::DEFAULT_ROLE_EMPLOYEE_CODE => [
				[
					'accessRights' => [
						[
							'id' => SignPermissionDictionary::SIGN_MY_SAFE_DOCUMENTS,
							'value' => UserPermissions::PERMISSION_SELF,
						],
						[
							'id' => SignPermissionDictionary::SIGN_TEMPLATES,
							'value' => UserPermissions::PERMISSION_ALL,
						],
						[
							'id' => SignPermissionDictionary::SIGN_MY_SAFE,
							'value' => 1,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_MY_SAFE,
							'value' => 1,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_MY_SAFE_DOCUMENTS,
							'value' => UserPermissions::PERMISSION_SELF,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_MY_SAFE_FIRED,
							'value' => UserPermissions::PERMISSION_NONE,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATES,
							'value' => UserPermissions::PERMISSION_SELF,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATE_WRITE,
							'value' => UserPermissions::PERMISSION_SELF,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATE_CREATE,
							'value' => UserPermissions::PERMISSION_SELF,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATE_READ,
							'value' => UserPermissions::PERMISSION_SELF,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATE_DELETE,
							'value' => UserPermissions::PERMISSION_SELF,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_SIGNERS_LIST_REFUSED,
							'value' => UserPermissions::PERMISSION_NONE,
						],
					],
				],
			],
			SignRolePermissionService::DEFAULT_ROLE_CHIEF_CODE =>[
				[
					'accessRights' => [
						[
							'id' => SignPermissionDictionary::SIGN_MY_SAFE_DOCUMENTS,
							'value' => UserPermissions::PERMISSION_ALL,
						],
						[
							'id' => SignPermissionDictionary::SIGN_TEMPLATES,
							'value' => UserPermissions::PERMISSION_ALL,
						],
						[
							'id' => SignPermissionDictionary::SIGN_MY_SAFE,
							'value' => 1,
						],
						[
							'id' => SignPermissionDictionary::SIGN_ACCESS_RIGHTS,
							'value' => 1,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_MY_SAFE,
							'value' => 1,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_MY_SAFE_DOCUMENTS,
							'value' => UserPermissions::PERMISSION_SUBDEPARTMENT,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_MY_SAFE_FIRED,
							'value' => UserPermissions::PERMISSION_NONE,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATES,
							'value' => UserPermissions::PERMISSION_SUBDEPARTMENT,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATE_WRITE,
							'value' => UserPermissions::PERMISSION_SUBDEPARTMENT,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATE_CREATE,
							'value' => UserPermissions::PERMISSION_SUBDEPARTMENT,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATE_READ,
							'value' => UserPermissions::PERMISSION_SUBDEPARTMENT,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATE_DELETE,
							'value' => UserPermissions::PERMISSION_SUBDEPARTMENT,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_SIGNERS_LIST_REFUSED,
							'value' => UserPermissions::PERMISSION_NONE,
						],
					],
				],
			],
		];

		$installed = false;
		while ($role = $roles->Fetch())
		{
			foreach ($rolesToInstall as $roleToInstall => $permission)
			{
				if ($role['CODE'] === $roleToInstall)
				{
					$permission[0]['id'] = $role['ID'];
					(new SignRolePermissionService())->saveRolePermissions($permission);
					$installed = true;
				}
			}
		}

		if ($installed)
		{
			return '';
		}

		return '\Bitrix\Sign\Access\Install\AccessInstaller::install();';
	}
}
