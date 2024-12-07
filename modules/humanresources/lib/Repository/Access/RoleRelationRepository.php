<?php

namespace Bitrix\HumanResources\Repository\Access;

use Bitrix\Main\DB\Result;
use Bitrix\HumanResources\Model\Access\AccessRoleRelationTable;

class RoleRelationRepository implements \Bitrix\HumanResources\Contract\Repository\Access\RoleRelationRepository
{
	public function getRolesByRelationCodes(array $relationCode): array
	{
		$rolesIds = [];
		$roles =
			AccessRoleRelationTable::query()
				->addSelect('ROLE_ID')
				->whereIn('RELATION', $relationCode)
				->fetchAll()
		;

		foreach ($roles as $role)
		{
			$rolesIds[] = (int)$role['ROLE_ID'];
		}

		return $rolesIds;
	}

	public function deleteRelationsByRoleId(int $roleId): Result
	{
		return AccessRoleRelationTable::deleteList(["=ROLE_ID" => $roleId]);
	}

	public function getRelationList(array $parameters = []): array
	{
		return AccessRoleRelationTable::getList($parameters)->fetchAll();
	}
}