<?php

namespace Bitrix\HumanResources\Service\Access;

use Bitrix\HumanResources\Access\Role\RoleUtil;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;
use Bitrix\Main\DB\SqlQueryException;

class RoleRelationService
{
	/**
	 * @throws RoleRelationSaveException
	 */
	public function saveRoleRelation(array $settings): void
	{
		foreach ($settings as $setting)
		{
			$roleId = $setting['id'];
			if ($roleId === false)
			{
				continue;
			}

			(new RoleUtil($roleId))->updateRoleRelations($setting['accessCodes'] ?? []);
		}

		Container::getCacheManager()->clean(NodeRepository::NODE_ENTITY_RESTRICTION_CACHE);
	}

	public function deleteRelationsByRoleId(int $roleId): void
	{
		Container::getAccessRoleRelationRepository()->deleteRelationsByRoleId($roleId);
	}

	/**
	 * @param array<int> $roleId
	 */
	public function deleteRelationsByRoleIds(array $roleIds): void
	{
		Container::getAccessRoleRelationRepository()->deleteRelationsByRoleIds($roleIds);
	}

	public function getRelationList(array $parameters = []): array
	{
		return Container::getAccessRoleRelationRepository()->getRelationList($parameters);
	}
}