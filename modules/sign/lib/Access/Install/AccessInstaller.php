<?php

namespace Bitrix\Sign\Access\Install;

use Bitrix\Crm\Integration\Sign\Access\Service\RolePermissionService;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Sign\Access\Permission\PermissionDictionary;
use Bitrix\Sign\Access\Permission\PermissionTable;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService as SignRolePermissionService;
use CCrmPerms;
use CCrmRole;

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
			['=GROUP_CODE' => RolePermissionService::ROLE_GROUP_CODE]
		);

		$rolesToInstall = [
			RolePermissionService::ROLE_GROUP_CODE . "_EMPLOYMENT" => [
				[
					'accessRights' =>
					[
						[
							'id' => SignPermissionDictionary::SIGN_MY_SAFE_DOCUMENTS,
							'value' => CCrmPerms::PERM_SELF,
						],
						[
							'id' => SignPermissionDictionary::SIGN_TEMPLATES,
							'value' => CCrmPerms::PERM_ALL,
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
							'value' => CCrmPerms::PERM_SELF,
						],
						[
							'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATES,
							'value' => CCrmPerms::PERM_SELF,
						],
					]
				]],
			RolePermissionService::ROLE_GROUP_CODE . "_CHIEF" =>[
				[
					'accessRights' =>
						[
							[
								'id' => SignPermissionDictionary::SIGN_MY_SAFE_DOCUMENTS,
								'value' => CCrmPerms::PERM_ALL,
							],
							[
								'id' => SignPermissionDictionary::SIGN_TEMPLATES,
								'value' => CCrmPerms::PERM_ALL,
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
								'value' => CCrmPerms::PERM_SUBDEPARTMENT,
							],
							[
								'id' => SignPermissionDictionary::SIGN_B2E_TEMPLATES,
								'value' => CCrmPerms::PERM_SUBDEPARTMENT,
							],
						]
				],
		]];

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