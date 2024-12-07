<?php

namespace Bitrix\BIConnector\Access\Service;

use Bitrix\BIConnector\Access\Role\RoleRelationTable;
use Bitrix\BIConnector\Access\Role\RoleUtil;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;
use Bitrix\Main;

final class RoleRelationService
{
	/**
	 * @param array $settings
	 * @throws RoleRelationSaveException
	 */
	public function saveRoleRelation(array $settings): void
	{
		foreach ($settings as $setting)
		{
			$roleId = $setting['id'];
			$accessCodes = $setting['accessCodes'] ?? [];

			if($roleId === false)
			{
				continue;
			}

			(new RoleUtil($roleId))->updateRoleRelations($accessCodes);
		}
	}

	public function deleteRoleRelations(int $roleId): Main\DB\Result
	{
		return RoleRelationTable::deleteList(['=ROLE_ID' => $roleId]);
	}
}
