<?php

namespace Bitrix\HumanResources\Install\Agent\Access;

use Bitrix\HumanResources\Access\Role\RoleDictionary;
use Bitrix\HumanResources\Repository\Access\RoleRelationRepository;
use Bitrix\HumanResources\Access\Role;
use Bitrix\Main\Access\AccessCode;

final class ResetDefaultRoleKeysAgent
{
	private const DEFAULT_ROLES = [
		RoleDictionary::ROLE_ADMIN => 'G1',
		RoleDictionary::ROLE_DIRECTOR=> AccessCode::ACCESS_DIRECTOR . '0',
		RoleDictionary::ROLE_EMPLOYEE=> AccessCode::ACCESS_EMPLOYEE . '0',
	];

	public static function run(): string
	{
		self::resetDefaultRoleKeys();

		return '';
	}

	private static function resetDefaultRoleKeys(): void
	{
		foreach (self::DEFAULT_ROLES as $roleName => $accessCode)
		{
			try
			{
				$roleRelationRepository = new RoleRelationRepository();
				$roleIds = $roleRelationRepository->getRolesByRelationCodes([$accessCode]);
				if (!empty($roleIds))
				{
					$roleUtil = new Role\RoleUtil($roleIds[0]);
					$roleUtil->updateTitle($roleName);
				}
			}
			catch (\Exception $e)
			{}
		}
	}
}