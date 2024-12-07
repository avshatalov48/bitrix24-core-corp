<?php

namespace Bitrix\BIConnector\Access\Install\Role;

use Bitrix\Crm\Security\Role\Model\RoleTable;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Loader;
use Bitrix\BIConnector\Access\Permission\PermissionDictionary;

class Manager extends Base
{
	public function getPermissions(): array
	{
		return [
			PermissionDictionary::BIC_ACCESS,
			PermissionDictionary::BIC_DASHBOARD_VIEW,
		];
	}

	protected function getRelationUserGroups(): array
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return [];
		}

		if ($this->isNewPortal)
		{
			return [AccessCode::ACCESS_EMPLOYEE . '0'];
		}

		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$crmRoles = RoleTable::query()
			->where('IS_SYSTEM', 'N')
			->setSelect(['ID'])
			->fetchAll()
		;
		$crmRoleIds = array_column($crmRoles, 'ID');

		$permissionCodes = [];
		$relations = \CCrmRole::GetRelation();
		while ($relation = $relations->Fetch())
		{
			if (in_array($relation['ROLE_ID'], $crmRoleIds, true))
			{
				$permissionCodes[$relation['RELATION']] = true;
			}
		}

		return array_keys($permissionCodes);
	}
}